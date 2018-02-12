$(function() {

  $("div.search[data-examplesurl][data-searchurl][data-statuscreateurl][data-statusstarturl][data-statusupdateurl][data-statuscancelurl]").each(function() {
    initSearch($(this), "");
  });

  $("div.test[data-examplesurl][data-searchurl]").each(function() {
    initTest($(this));
  });

  $("div.mapping[data-mappingurl]").each(function() {
    initMapping($(this));
  });

  function initMapping(container) {
    var request = {
      "action" : "info"
    };
    $.ajax({
      "type" : "POST",
      "url" : container.data("mappingurl"),
      "data" : JSON.stringify(request),
      "contentType" : "application/json",
      "success" : function(data) {
        createMapping(container, data);
      }
    });
  }

  function createMapping(container, info) {
    var mappingData;
    var tabs = $("<div/>").addClass("tabs");
    // result screen
    var mappingResult = $("<div/>").addClass("mappingresult");
    // resource screen
    var mappingResource = $("<div/>").addClass("mappingcontent").hide();
    var inputResourceUrl = $("<input/>").attr("type", "url").attr("placeholder", "url resource");
    var inputResourceFile = $("<input/>").attr("type", "file");
    var inputResourceTextarea = $("<textarea/>");
    inputResourceFile.change(function() {
      var file = this.files[0];
      name = file.name;
      size = file.size;
      if (file.name.length < 1) {
        inputConfigurationFile.val("");
      } else {
        var reader = new FileReader();
        reader.onloadend = function() {
          inputResourceTextarea.val(reader.result);
          inputResourceFile.val("");
          checkForm();
        };
        reader.onerror = function() {
          alert(reader.error);
          inputResourceFile.val("");
        };
        reader.readAsText(file, "UTF-8");
      }
    });
    inputResourceUrl.on("change keyup paste", function() {
      checkForm();
    });
    inputResourceTextarea.on("change keyup paste", function() {
      checkForm();
    });
    mappingResource.append($("<div/>").addClass("mappingblock").append(inputResourceUrl));
    mappingResource.append($("<div/>").addClass("mappingblock").append(inputResourceFile));
    mappingResource.append($("<div/>").addClass("textarea").append(inputResourceTextarea));
    var buttonResourceReset = $("<button/>").text("Reset").click(function() {
      resetMapping();
    });
    var buttonResourceSend = $("<button/>").addClass("send").text("Test mapping").click(function() {
      doMapping();
    });
    var buttonsResource = $("<div/>").addClass("buttons");
    buttonsResource.append(buttonResourceReset);
    buttonsResource.append(buttonResourceSend);
    mappingResource.append(buttonsResource);
    // config screen
    var mappingConfiguration = $("<div/>").addClass("mappingcontent").hide();
    var listConfigurationFiles = $("<div/>").text("");
    var inputConfigurationTextarea = $("<textarea/>");
    var inputConfigurationFile = $("<input/>").attr("type", "file");
    var inputConfigurationCore = $("<select/>").attr("size", "1").attr("placeholder", "url resource");
    inputConfigurationCore.append($("<option/>").attr("disabled", "true").attr("value", "").attr("selected", "true").text("--- Select Solr Configuration ---"));
    if (info && info.configurations) {
      for ( var configuration in info.configurations) {
        if (info.configurations.hasOwnProperty(configuration)) {
          inputConfigurationCore.append($("<option/>").attr("value", configuration).text(configuration));
        }
      }
      inputConfigurationCore.change(function() {
        checkForm();
        listConfigurationFiles.text("");
        if (this.value && info.configurations.hasOwnProperty(this.value)) {
          listConfigurationFiles.closest("div.right").show();
          inputConfigurationFile.closest("div.mappingblock").show();
          inputConfigurationTextarea.closest("div.textarea").show();
          listConfigurationFiles.append($("<div/>").addClass("title").text(this.value));
          if (info.configurations[this.value].hasOwnProperty("files")) {
            for ( var file in info.configurations[this.value]["files"]) {
              var fileItem = $("<div/>").addClass("link");
              fileItem.text(info.configurations[this.value]["files"][file]);
              fileItem.data("configuration", this.value);
              fileItem.data("file", info.configurations[this.value]["files"][file]);
              listConfigurationFiles.append(fileItem);
            }
            listConfigurationFiles.find("div.link").click(function() {
              var request = {
                "action" : "file",
                "file" : $(this).data("file"),
                "configuration" : $(this).data("configuration")
              };
              var oThis = $(this);
              $.ajax({
                "type" : "POST",
                "url" : container.data("mappingurl"),
                "data" : JSON.stringify(request),
                "contentType" : "application/json",
                "success" : function(data) {
                  if (data.hasOwnProperty("data")) {
                    inputConfigurationTextarea.val(data.data);
                    oThis.siblings().removeClass("selected");
                    oThis.addClass("selected");
                  } else {
                    inputConfigurationTextarea.val("");
                  }
                  checkForm();
                }
              });
            });
          }
        }
      });
    }
    inputConfigurationFile.change(function() {
      listConfigurationFiles.find("div.link").removeClass("selected");
      var file = this.files[0];
      name = file.name;
      size = file.size;
      if (file.name.length < 1) {
        inputConfigurationFile.val("");
      } else {
        var reader = new FileReader();
        reader.onloadend = function() {
          inputConfigurationTextarea.val(reader.result);
          inputConfigurationFile.val("");
          checkForm();
        };
        reader.onerror = function() {
          alert(reader.error);
          inputConfigurationFile.val("");
        };
        reader.readAsText(file, "UTF-8");
      }
    });
    inputConfigurationTextarea.on("change keyup paste", function() {
      listConfigurationFiles.find("div.link").removeClass("selected");
      checkForm();
    });
    mappingConfiguration.append($("<div/>").addClass("right").append(listConfigurationFiles));
    mappingConfiguration.append($("<div/>").addClass("mappingblock").append(inputConfigurationCore));
    mappingConfiguration.append($("<div/>").addClass("mappingblock").append(inputConfigurationFile));
    mappingConfiguration.append($("<div/>").addClass("textarea").append(inputConfigurationTextarea));
    var buttonConfigurationReset = $("<button/>").text("Reset").click(function() {
      resetMapping();
    });
    var buttonConfigurationSend = $("<button/>").addClass("send").text("Test mapping").click(function() {
      doMapping();
    });
    var buttonsConfiguration = $("<div/>").addClass("buttons");
    buttonsConfiguration.append(buttonConfigurationReset);
    buttonsConfiguration.append(buttonConfigurationSend);
    mappingConfiguration.append(buttonsConfiguration);
    // define tabs
    var tabResource = $("<div/>").addClass("tab").text("Resource").click(function() {
      tabs.find("div.tab").each(function() {
        $(this).removeClass("selected");
      });
      mappingConfiguration.hide();
      mappingResource.show();
      $(this).addClass("selected");
    });
    var tabConfiguration = $("<div/>").addClass("tab").text("Configuration").click(function() {
      tabs.find("div.tab").each(function() {
        $(this).removeClass("selected");
      });
      mappingResource.hide();
      mappingConfiguration.show();
      $(this).addClass("selected");
    });
    tabs.append(tabResource);
    tabs.append(tabConfiguration);
    // fill container
    container.html("");
    container.append(tabs);
    container.append(mappingResource);
    container.append(mappingConfiguration);
    container.append(mappingResult);
    tabResource.addClass("selected");
    mappingResource.show();
    resetMapping();
    function doMapping() {
      var request = {
        "action" : "mapping",
        "document" : inputResourceTextarea.val(),
        "url" : inputResourceUrl.val(),
        "configuration" : inputConfigurationCore.val(),
        "mapping" : inputConfigurationTextarea.val()
      };
      var oThis = $(this);
      $.ajax({
        "type" : "POST",
        "url" : container.data("mappingurl"),
        "data" : JSON.stringify(request),
        "contentType" : "application/json",
        "success" : function(data) {
          oThis.siblings().removeClass("selected");
          oThis.addClass("selected");
          mappingData = false;
          if (data.hasOwnProperty("data")) {
            mappingData = data.data;
            createResult(0, 100);         
          } else {
            alert("no data");
          }
          checkForm();
        }
      });
    }
    function resetMapping() {
      mappingData = false;
      inputResourceUrl.val("");
      inputResourceFile.val("");
      inputResourceTextarea.val("")
      inputConfigurationCore.val("").change();
      inputConfigurationFile.val("");
      listConfigurationFiles.parent().hide();
      inputConfigurationFile.parent().hide();
      inputConfigurationTextarea.val("").parent().hide();
      mappingResult.html("").hide();
      checkForm();
    }
    function createResult(start, step) {
      mappingResult.html("");      
      if(mappingData && mappingData.hasOwnProperty("mapping") && mappingData.mapping.length>0) {
        var topMenu = $("<div/>").addClass("navigation");
        if(start>0) {
          var topMenuPrevious = $("<div/>").addClass("navigationLeft").append($("<button/>").text("previous "+step).click(function() {
            createResult((start-step), step);
          }));
          topMenu.append(topMenuPrevious);
        }
        if(start+step<mappingData.mapping.length) {
          var topMenuNext = $("<div/>").addClass("navigationRight").append($("<button/>").text("next "+Math.min(step,(mappingData.mapping.length-start-step-1))).click(function() {
            createResult((start+step), step);
          }));
          topMenu.append(topMenuNext);
        }
        var last = Math.min((start+step), (mappingData.mapping.length-1));
        var topMenuInfo = $("<div/>").addClass("navigationInfo").text((start+1)+" - "+last+" from "+(mappingData.mapping.length-1));
        topMenu.append(topMenuInfo);
        mappingResult.append(topMenu);
        var table = $("<table/>");
        var trTitle = $("<tr/>").addClass("title");
        for(var j=0; j<mappingData.mapping[0].length; j++) {
          trTitle.append($("<td/>").text(mappingData.mapping[0][j]));
        }
        table.append(trTitle);
        for(var i=start; i<last; i++) {
          var tr = $("<tr/>");
          for(var j=0; j<mappingData.mapping[(i+1)].length; j++) {
            tr.append($("<td/>").text(mappingData.mapping[(i+1)][j]));
          }
          table.append(tr);
        }        
        mappingResult.append(table);
      } 
      mappingResult.show();
    }
    function checkForm() {
      var problems = []
      if (inputResourceTextarea.val().trim() == "" && inputResourceUrl.val().trim() == "") {
        problems.push("no resource defined");
      }
      if (inputResourceUrl.val().trim() == "") {
        inputResourceTextarea.prop("disabled", false);
        inputResourceFile.closest("div").show();
      } else {
        inputResourceTextarea.prop("disabled", true);
        inputResourceFile.closest("div").hide();
      }
      if (!inputConfigurationCore.val() || inputConfigurationCore.val().trim() == "") {
        problems.push("no solr configuration selected");
      } else if (!inputConfigurationTextarea || inputConfigurationTextarea.val().trim() == "") {
        problems.push("no configuration defined");
      }
      if (problems.length == 0) {
        buttonResourceSend.prop("disabled", false).attr("title", null);
        buttonConfigurationSend.prop("disabled", false).attr("title", null);
      } else {
        buttonResourceSend.prop("disabled", true).attr("title", problems.join("\n"));
        buttonConfigurationSend.prop("disabled", true).attr("title", problems.join("\n"));
      }
    }
  }
  function initTest(container) {
    createTest(container);
  }

  function createTest(container) {
    var controls = $("<div/>").addClass("block").addClass("controls");
    var controlsCache = $("<label/>").addClass("cache");
    var controlsCacheCheckBox = $("<input/>").attr("type", "checkbox");
    var controlsStartButton = $("<button/>").addClass("left").addClass("start").text("Start tests").click(function() {
      startTests(container, controlsCacheCheckBox.prop("checked"));
    });
    controlsCache.append(controlsCacheCheckBox);
    controlsCache.append($("<span/>").text("cache enabled"));
    var status = $("<div/>").addClass("status");
    var errors = $("<div/>").addClass("errors");
    var errorsTable = $("<table/>");
    errors.append(errorsTable);
    container.find("div.controls").append(errors);
    var controlsResetButton = $("<button/>").addClass("left").addClass("reset").text("Reset").click(function() {
      resetTests(container);
    });
    controls.append(controlsStartButton.hide());
    controls.append(controlsCache.hide());
    controls.append(status.hide());
    controls.append(errors.hide());
    controls.append(controlsResetButton.hide());
    container.append(controls);
    var examples = $("<div/>").addClass("block").addClass("examples");
    var examplesTable = $("<table/>");
    examples.append(examplesTable.hide());
    container.append(examples);
    $.ajax({
      "type" : "GET",
      "url" : container.data("examplesurl"),
      "dataType" : "text",
      "cache" : false
    }).done(function(data) {
      try {
        var obj = JSON.parse(data);
        if (obj.examples !== undefined && $.isArray(obj.examples)) {
          for (var i = 0; i < obj.examples.length; i++) {
            if (obj.examples[i].title !== undefined && obj.examples[i].url !== undefined && obj.examples[i].code !== undefined) {
              examplesTable.append(createTestItem(container, i, obj.examples[i].title, obj.examples[i].url, obj.expansion, obj.solr));
            }
          }
        }
        examplesTable.show();
        controlsStartButton.show();
        controlsCache.show();
      } catch (err) {
        // do nothing
      }
    });
  }

  function createTestItem(container, id, title, url, expansionConfig, solrConfig) {
    var row = $("<tr/>");
    var link = $("<a/>").attr("href", container.data("searchurl") + "#editor-examples-" + id).text(title)
    row.append($("<td/>").addClass("title").append(link));
    var cell = $("<td/>").addClass("examples").html("&nbsp;");
    row.append(cell);
    $.get({
      "url" : url,
      "cache" : false
    }, function(data) {
      var html = $("<div/>").append(customizeText(data, expansionConfig, solrConfig));
      var table = $("<table/>");
      html.find("div.example td.button").each(function() {
        var tableRow = $("<tr/>");
        tableRow.append($("<td/>").addClass("title").text($(this).find("button.json").first().html()));
        tableRow.append($("<td/>").addClass("time").attr("title", "time").html("&nbsp;"))
        tableRow.append($("<td/>").addClass("warnings").attr("title", "warnings").html("&nbsp;"))
        tableRow.append($("<td/>").addClass("errors").attr("title", "errors").html("&nbsp;"))
        tableRow.data("json", $(this).find("div.json").first().text());
        tableRow.data("title", title);
        table.append(tableRow);
      });
      cell.append(table);
    });
    return row;
  }

  function startTests(container, doCache) {
    var queue = [];
    var numbers = {
      "total" : 0,
      "finished" : 0,
      "errors" : 0,
      "warnings" : 0
    };
    var startButton = container.find("div.controls button.start");
    var cacheControl = container.find("div.controls label.cache");
    var testStatus = container.find("div.controls div.status");
    var errors = container.find("div.controls div.errors");
    var errorsTable = errors.find("table");
    if (startButton.is(":visible")) {
      startButton.hide();
      cacheControl.hide();
      testStatus.show();
      errors.show();
      errorsTable.html("");
      container.find("div.examples td.examples table tr").each(function() {
        queue.push($(this));
      })
      numbers.total = queue.length;
      processQueue(container, queue, numbers, doCache);
    }
  }

  function processQueue(container, queue, numbers, doCache) {
    if (queue.length > 0) {
      var testStatus = container.find("div.controls div.status");
      var errors = container.find("div.controls div.errors");
      var errorsTable = errors.find("table");
      var item = queue.shift();
      var maintitle = item.data("title");
      var subtitle = item.find("td.title").text();
      var statusTime = item.find("td.time");
      var statusWarnings = item.find("td.warnings");
      var statusErrors = item.find("td.errors");
      var text = item.data("json");
      var startTime = new Date().getTime();
      try {
        var obj = JSON.parse(text);
        if (doCache) {
          obj.cache = true;
        } else {
          obj.cache = false;
        }
        text = JSON.stringify(obj);
        $.ajax({
          "type" : "POST",
          "url" : container.data("searchurl"),
          "dataType" : "text",
          "data" : text,
          "contentType" : "application/json",
          "success" : function(data, requestStatus, xhr) {
            var totalTime = new Date().getTime() - startTime;
            var warnings = xhr.getResponseHeader("X-Broker-warnings");
            if (warnings != null) {
              warnings = parseInt(warnings);
              if (warnings > 0) {
                numbers.warnings++;
                statusWarnings.addClass("error");
              } else {
                statusWarnings.addClass("ready");
              }
              statusWarnings.text(warnings);
            } else {
              statusWarnings.text("---");
            }
            var errors = xhr.getResponseHeader("X-Broker-errors");
            if (errors != null) {
              errors = parseInt(errors);
              if (errors > 0) {
                statusErrors.addClass("error");
              } else {
                statusErrors.addClass("ready");
              }
              statusErrors.text(errors);
            } else {
              statusErrors.text("---");
            }
            statusTime.text(parseInt(totalTime).toLocaleString("en-UK") + " ms");
            try {
              var response = JSON.parse(data);
              if (response.response == undefined || response.response.numFound == undefined || response.response.numFound === 0) {
                var errorRow = $("<tr/>");
                errorRow.append($("<td/>").text("no results"));
                errorRow.append($("<td/>").text(maintitle));
                errorRow.append($("<td/>").text(subtitle));
                errorRow.append($("<td/>").attr("title", "request").text(text));
                errorRow.append($("<td/>").attr("title", "error").text(data));
                errorsTable.append(errorRow);
              }
              statusTime.addClass("ready");
            } catch (err) {
              statusTime.addClass("error");
              var errorRow = $("<tr/>");
              errorRow.append($("<td/>").text("no valid json"));
              errorRow.append($("<td/>").text(maintitle));
              errorRow.append($("<td/>").text(subtitle));
              errorRow.append($("<td/>").attr("title", "request").text(text));
              errorRow.append($("<td/>").attr("title", "error").text(data));
              errorsTable.append(errorRow);
              numbers.errors++;
            }
          },
          "error" : function(xhr, textStatus) {
            var totalTime = new Date().getTime() - startTime;
            var warnings = xhr.getResponseHeader("X-Broker-warnings");
            if (warnings != null) {
              warnings = parseInt(warnings);
              if (warnings > 0) {
                numbers.warnings++;
                statusWarnings.addClass("error");
              } else {
                statusWarnings.addClass("ready");
              }
              statusWarnings.text(warnings);
            } else {
              statusWarnings.text("---");
            }
            var errors = xhr.getResponseHeader("X-Broker-errors");
            if (errors != null) {
              errors = parseInt(errors);
              if (errors > 0) {
                statusErrors.addClass("error");
              } else {
                statusErrors.addClass("ready");
              }
              statusErrors.text(errors);
            } else {
              statusErrors.text("---");
            }
            statusTime.text(parseInt(totalTime).toLocaleString("en-UK") + " ms");
            statusTime.addClass("error");
            var errorRow = $("<tr/>");
            errorRow.append($("<td/>").text(textStatus));
            errorRow.append($("<td/>").text(maintitle));
            errorRow.append($("<td/>").text(subtitle));
            errorRow.append($("<td/>").attr("title", "request").text(text));
            errorRow.append($("<td/>").attr("title", "error").text(xhr.responseText));
            errorsTable.append(errorRow);
            numbers.errors++;
          },
          "complete" : function() {
            processQueue(container, queue, numbers, doCache);
            numbers.finished++;
            testStatus
                .text("Performing " + numbers.total + " requests, finished " + numbers.finished + " with " + numbers.errors + " resulting in error(s) and " + numbers.warnings + " in warning(s)");
          }
        });
      } catch (err) {
        alert("something went wrong: "+err.message);
      }
    } else {
      container.find("div.controls button.reset").show();
    }
  }

  function resetTests(container) {
    var resetButton = container.find("div.controls button.reset");
    var startButton = container.find("div.controls button.start");
    var cacheControl = container.find("div.controls label.cache");
    var status = container.find("div.controls div.status");
    var errors = container.find("div.controls div.errors");
    var errorsTable = errors.find("table");
    if (!startButton.is(":visible")) {
      resetButton.hide();
      status.html("").hide();
      errors.hide();
      errorsTable.html("");
      startButton.show();
      cacheControl.show();
      container.find("div.examples table tr td.examples table td.time").removeClass("error").removeClass("ready").html("&nbsp;");
      container.find("div.examples table tr td.examples table td.warnings").removeClass("error").removeClass("ready").html("&nbsp;");
      container.find("div.examples table tr td.examples table td.errors").removeClass("error").removeClass("ready").html("&nbsp;");
    }
  }

  function initSearch(container, text) {
    if (container.data("id") == undefined) {
      if ($(document).data("editorNumber") !== undefined) {
        $(document).data("editorNumber", $(document).data("editorNumber") + 1);
      } else {
        $(document).data("editorNumber", 0);
      }
      container.data("id", "editor" + ($(document).data("editorNumber") > 0 ? $(document).data("editorNumber") : ""));
    }
    if (getCookie("brokerCache") != null) {
      if (getCookie("brokerCache") === "enabled") {
        container.data("defaultCache", true);
      } else if (getCookie("brokerCache") === "disabled") {
        container.data("defaultCache", false);
      }
    } else if (container.data("defaultCache") == undefined) {
      container.data("defaultCache", true);
    }
    if (getCookie("brokerStatus") != null) {
      if (getCookie("brokerStatus") === "enabled") {
        container.data("defaultStatus", true);
      } else if (getCookie("brokerStatus") === "disabled") {
        container.data("defaultStatus", false);
      }
    } else if (container.data("defaultStatus") == undefined) {
      container.data("defaultStatus", true);
    }
    container.html("");
    var editor = $("<div/>").addClass("block").addClass("editor");
    var editorTextarea = $("<textarea/>");
    editorTextarea.attr("spellcheck", "false");
    editorTextarea.val(text);
    editorTextarea.on("keyup", function() {
      updateTextarea($(this), false);
    });
    editorTextarea.on("pastejson", function() {
      updateTextarea($(this), true);
      alignJson($(this));
    });
    var editorMenu = $("<div/>").addClass("menu");
    var editorMenuReset = $("<button/>").addClass("left").text("Reset").click(function() {
      editorTextarea.val("");
      updateTextarea(editorTextarea, false);
      resetRequestResponse(container);
      container.find("div.examples").show();
    });
    var editorMenuAlignJson = $("<button/>").addClass("left").text("Align json").click(function() {
      alignJson(editorTextarea);
    });
    var editorMenuSubmit = $("<button/>").addClass("left").addClass("send").text("Send request").click(function() {
      if (container.data("defaultStatus") !== undefined && container.data("defaultStatus") == true) {
        doRequest(editorTextarea, true);
      } else {
        doRequest(editorTextarea, false);
      }
    });
    var editorMenuCache = $("<button/>").addClass("json").addClass("right").attr("data-request", "cache").text("Cache").click(function() {
      if ($(this).hasClass("enabled")) {
        updateEditor(editorTextarea, "cache", false);
      } else {
        updateEditor(editorTextarea, "cache", true);
      }
    });
    if (getCookie("brokerCache") !== null && getCookie("brokerCache") === "enabled") {
      editorMenuCache.addClass("enabled");
    }
    var editorMenuStatus = $("<button/>").addClass("json").addClass("right").attr("data-request", "status").text("Status").click(function() {
      if ($(this).hasClass("enabled")) {
        updateEditor(editorTextarea, "status", false);
      } else {
        updateEditor(editorTextarea, "status", true);
      }
    });
    if (getCookie("brokerStatus") !== null && getCookie("brokerStatus") === "enabled") {
      editorMenuCache.addClass("enabled");
    }
    var editorMenuRemember = $("<input/>").addClass("right").addClass("json").addClass("checkbox").attr("data-request", "remember").attr("title", "remember").attr("type", "checkbox").attr(
        "data-request", "remember").click(function(event) {
      if ($(this).prop("checked")) {
        storeCookie("brokerCache", editorMenuCache.hasClass("enabled") ? "enabled" : "disabled");
        storeCookie("brokerStatus", editorMenuStatus.hasClass("enabled") ? "enabled" : "disabled");
      } else {
        deleteCookie("brokerCache");
        deleteCookie("brokerStatus");
      }
    });
    if (getCookie("brokerCache") !== null || getCookie("brokerStatus") !== null) {
      editorMenuRemember.prop("checked", true);
    }
    editorMenu.append(editorMenuReset);
    editorMenu.append(editorMenuAlignJson);
    editorMenu.append(editorMenuSubmit);
    editorMenu.append(editorMenuCache);
    editorMenu.append(editorMenuStatus);
    editorMenu.append(editorMenuRemember);
    editor.append(editorTextarea);
    editor.append(editorMenu);
    var status = $("<div/>").addClass("block").addClass("status");
    status.append($("<div/>").addClass("title"));
    status.append(createCollapsable("broker", false));
    status.append(createCollapsable("solr", false));
    status.append(createCollapsable("warnings", false));
    status.append(createCollapsable("errors", false));
    status.append(createCollapsable("info", false));
    status.append($("<div/>").addClass("progress"));
    // response section
    var response = $("<div/>").addClass("block").addClass("response");
    response.append($("<div/>").addClass("title"));
    response.append(createCollapsable("error", true));
    response.append(createCollapsable("content", true));
    // add to container
    container.append($("<a/>").attr("id", container.data("id") + "-editor"));
    container.append(editor);
    container.append($("<a/>").attr("id", container.data("id") + "-status"));
    container.append(status);
    container.append($("<a/>").attr("id", container.data("id") + "-response"));
    container.append(response);
    container.append($("<a/>").attr("id", container.data("id") + "-examples"));
    container.append(createExamples(container));
    container.append($("<br/>"));
    // final settings
    editorTextarea.focus();
    updateTextarea(editorTextarea, false);
    resetRequestResponse(container);
  }
  function createExamples(container) {
    var examples = $("<div/>").addClass("block").addClass("examples");
    var examplesTitle = $("<div/>").addClass("title").text("Manual");
    examples.append(examplesTitle);
    var examplesContent = $("<div/>").addClass("content");
    var examplesContentMenu = $("<div/>").addClass("menu");
    var examplesContentDetails = $("<div/>").addClass("details");
    examplesContent.append(examplesContentMenu);
    examplesContent.append(examplesContentDetails);
    examples.append(examplesContent);
    var hash = window.location.hash.substr(1);
    $.ajax({
      "type" : "GET",
      "url" : container.data("examplesurl"),
      "dataType" : "text",
      "cache" : false
    }).done(function(data) {
      try {
        var obj = JSON.parse(data);
        var forceSelect = null;
        if (obj.examples !== undefined && $.isArray(obj.examples)) {
          for (var i = 0; i < obj.examples.length; i++) {
            if (obj.examples[i].title !== undefined && obj.examples[i].url !== undefined && obj.examples[i].code !== undefined) {
              var item = $("<div/>");
              item.addClass("item");
              item.data("url", obj.examples[i].url);
              item.data("id", i);
              item.data("name", obj.examples[i].title);
              item.data("code", obj.examples[i].code);
              if (obj.examples[i].code.match(/^[0-9]+_[0-9a-z]+_[0-9a-z]+$/)) {
                item.addClass("subsubitem");
                item.text("- " + obj.examples[i].title);
              } else if (obj.examples[i].code.match(/^[0-9]+_[0-9a-z]+$/)) {
                item.addClass("subitem");
                item.text("- " + obj.examples[i].title);
              } else {
                item.text(obj.examples[i].title);
              }
              item.data("parent", obj.examples[i].code.replace(/_[^_]*$/g, ""));
              if (item.data("parent") !== item.data("code")) {
                item.hide();
              }
              examplesContentMenu.append(item);
              if (hash !== undefined) {
                var hashes = hash.split(",");
                for (var j = 0; j < hashes.length; j++) {
                  if (hashes[j] == container.data("id") + "-examples-" + i) {
                    forceSelect = item;
                    j = hashes.length;
                  }
                }
              }
            }
          }
          enableExamples(container, examplesContentMenu, examplesContentDetails, obj.expansion, obj.solr);
          if (forceSelect !== null) {
            enableExample(forceSelect, container, examplesContentMenu, examplesContentDetails, obj.expansion, obj.solr);
          } else {
            enableExample(examplesContentMenu.find("div.item").first(), container, examplesContentMenu, examplesContentDetails, obj.expansion, obj.solr);
          }
        }
      } catch (err) {
        // do nothing
      }
      $(window).on("hashchange", function() {
        var hash = window.location.hash.substr(1);
        if (hash !== undefined) {
          var hashes = hash.split(",");
          examplesContentMenu.find("div.item").each(function() {
            var item = $(this);
            for (var j = 0; j < hashes.length; j++) {
              if (hashes[j] == container.data("id") + "-examples-" + item.data("id")) {
                enableExample(item, container, examplesContentMenu, examplesContentDetails, obj.expansion, obj.solr);
              }
            }
          });
        }
      });
    });
    return examples;
  }
  function enableExamples(container, examplesContentMenu, examplesContentDetails, expansionConfig, solrConfig) {
    examplesContentMenu.find("div.item").each(function() {
      $(this).click(function() {
        enableExample($(this), container, examplesContentMenu, examplesContentDetails, expansionConfig, solrConfig);
        var id = $(this).data("id");
        location.href = "#" + container.data("id") + "-examples-" + id;
      });
    });
  }
  function enableExample(item, container, examplesContentMenu, examplesContentDetails, expansionConfig, solrConfig) {
    var id = item.data("id");
    var parent = item.data("parent");
    var code = item.data("code");
    // reset menu
    examplesContentMenu.find("div.item").removeClass("selected");
    examplesContentMenu.find("div.item").removeClass("sectionSelected");
    examplesContentMenu.find("div.item").each(function() {
      if ($(this).data("id") == id) {
        $(this).show();
        $(this).addClass("selected");
      } else if ($(this).data("parent") == code) {
        $(this).show();
        $(this).addClass("sectionSelected");
      } else if ($(this).data("code").length <= parent.length && $(this).data("code") == parent.substr(0, $(this).data("code").length)) {
        $(this).show();
        $(this).addClass("sectionSelected");
      } else if (($(this).data("parent").length < parent.length) && $(this).data("parent") == parent.substr(0, $(this).data("parent").length)) {
        $(this).show();
      } else if ($(this).data("parent") == parent) {
        $(this).show();
      } else if ($(this).data("parent") == $(this).data("code")) {
        $(this).show();
      } else {
        $(this).hide();
      }
    });
    examplesContentMenu.closest("div.examples").find("> div.title").attr("id", container.data("id") + "-examples-" + id);
    $.get({
      "url" : item.data("url"),
      "cache" : false
    }, function(data) {
      examplesContentDetails.html(customizeText(data, expansionConfig, solrConfig));
      examplesContentDetails.find("button.json").off("click").click(function(event) {
        event.stopPropagation();
        var json = $(this).closest("div").find("div.json").text();
        container.find("textarea").val(json).trigger("pastejson");
        $(window).scrollTop(0);
      });
      examplesContentDetails.find("a[data-tab]").off("click").click(function(event) {
        event.stopPropagation();
        var tab = $(this).data("tab");
        examplesContentMenu.find("div.item").removeClass("selected");
        examplesContentDetails.html("");
        examplesContentMenu.find("div.item").each(function() {
          if ($(this).data("name") == tab) {
            $(this).trigger("click");
          }
        });
      });
      examplesContentDetails.find("a[data-section]").off("click").click(function(event) {
        event.stopPropagation();
        location.href = "#" + container.data("id") + "-examples-" + id + ",section-" + $(this).data("section");
      });
      examplesContentDetails.find("div[data-section]").each(function() {
        $(this).attr("id", container.data("id") + "-examples-" + id + ",section-" + $(this).data("section"));
      });
      var hash = location.hash;
      if (hash !== undefined) {
        location.hash = hash;
      }
    });
  }
  function customizeText(text, expansionConfig, solrConfig) {
    var property;
    if (text.match(/::configuration[0-9]+(\([^\)]+\))?::/)) {
      var configurations = Object.keys(solrConfig);
      text = text.replace(/::configuration([0-9]+)(\(([^\)]+)\))?::/g, function(match, p1, p2) {
        if (p1 < configurations.length) {
          return configurations[p1];
        } else {
          return p2;
        }
      });
    }
    for (property in solrConfig) {
      if (solrConfig.hasOwnProperty(property)) {
        if (text.match(/::uniqueKey::/)) {
          if (solrConfig[property].uniqueKey !== undefined) {
            text = text.replace(/::uniqueKey::/g, solrConfig[property].uniqueKey);
          }
        }
        if (text.match(/::space::/)) {
          text = text.replace(/::space::/g, " ");
        }
        if (text.match(/::ifMtasField\(([^\)]*)\)\(([^\)]*)\)::/)) {
          text = text.replace(/::ifMtasField\(([^\)]*)\)\(([^\)]*)\)::/g, function(match, p1, p2) {
            if (solrConfig[property].exampleFieldMtas !== undefined) {
              return p1;
            } else {
              return p2;
            }
          });
        }
        if (text.match(/::exampleFieldText(\([^\)]+\))?::/)) {
          if (solrConfig[property].exampleFieldText !== undefined) {
            text = text.replace(/::exampleFieldText(\([^\)]+\))?::/g, solrConfig[property].exampleFieldText);
          }
        }
        if (text.match(/::exampleFieldString(\([^\)]+\))?::/)) {
          if (solrConfig[property].exampleFieldString !== undefined) {
            text = text.replace(/::exampleFieldString(\([^\)]+\))?::/g, solrConfig[property].exampleFieldString);
          }
        }
        if (text.match(/::exampleFieldInteger(\([^\)]+\))?::/)) {
          if (solrConfig[property].exampleFieldInteger !== undefined) {
            text = text.replace(/::exampleFieldInteger(\([^\)]+\))?::/g, solrConfig[property].exampleFieldInteger);
          }
        }
        if (text.match(/::exampleFieldMtas(\([^\)]+\))?::/)) {
          if (solrConfig[property].exampleFieldMtas !== undefined) {
            text = text.replace(/::exampleFieldMtas(\([^\)]+\))?::/g, solrConfig[property].exampleFieldMtas);
          }
        }
        if (text.match(/::exampleFieldTextValue([0-9]+)(\([^\)]+\))?::/)) {
          if (solrConfig[property].exampleFieldTextValues !== undefined && solrConfig[property].exampleFieldTextValues !== null) {
            text = text.replace(/::exampleFieldTextValue([0-9]+)(\(([^\)]+)\))?::/g, function(match, p1, p2, p3) {
              if (solrConfig[property].exampleFieldTextValues.length >= (1 + parseInt(p1))) {
                return solrConfig[property].exampleFieldTextValues[parseInt(p1)];
              } else {
                return p3;
              }
            });
          }
        }
        if (text.match(/::exampleFieldIntegerValue([0-9]+)(\([^\)]+\))?::/)) {
          if (solrConfig[property].exampleFieldIntegerValues !== undefined && solrConfig[property].exampleFieldIntegerValues !== null) {
            text = text.replace(/::exampleFieldIntegerValue([0-9]+)(\(([^\)]+)\))?::/g, function(match, p1, p2, p3) {
              if (solrConfig[property].exampleFieldIntegerValues.length >= (1 + parseInt(p1))) {
                return solrConfig[property].exampleFieldIntegerValues[parseInt(p1)];
              } else {
                return p3;
              }
            });
          }
        }
        if (text.match(/::exampleFieldMtas(Escaped)?(Word|Lemma|Pos)(Value([0-9]+)|Prefix()|Postfix([0-9]+))(\([^\)]+\))?::/)) {
          if (solrConfig[property].exampleFieldMtas !== undefined) {
            text = text.replace(/::exampleFieldMtas(Escaped)?(Word|Lemma|Pos)?(Value|Prefix|Postfix)([0-9]*)(\(([^\)]+)\))?::/g, function(match, p1, p2, p3, p4, p5, p6) {
              var source = null;
              var value = p6;
              if (p2 == "Pos" && solrConfig[property].exampleFieldMtasPos !== undefined) {
                source = solrConfig[property].exampleFieldMtasPos;
              } else if (p2 == "Lemma" && solrConfig[property].exampleFieldMtasLemma !== undefined) {
                source = solrConfig[property].exampleFieldMtasLemma;
              } else if (p2 == "Word" && solrConfig[property].exampleFieldMtasWord !== undefined) {
                source = solrConfig[property].exampleFieldMtasWord;
              }
              if (source != null) {
                if (p3 == "Prefix") {
                  value = source[0];
                } else if (p3 == "Postfix" && p4 != "") {
                  if (source[1].length >= (1 + parseInt(p4))) {
                    value = source[1][parseInt(p4)];
                  } else if (source[1].length > 0) {
                    value = source[1][0];
                  }
                } else if (p3 == "Value" && p4 != "") {
                  if (source[1].length >= (1 + parseInt(p4))) {
                    value = source[0] + "=\"" + source[1][parseInt(p4)] + "\"";
                  } else if (source[1].length > 0) {
                    value = source[0] + "=\"" + source[1][0] + "\"";
                  }
                }
                if (p1 == "Escaped") {
                  value = value.replace(/\\/g, "\\\\").replace(/"/g, "\\\"");
                }
              }
              return value;
            });
          }
        }
      }
    }
    if (text.match(/::expansionTable::/)) {
      var table = $("<table/>").addClass("table");
      var row = $("<tr/>").addClass("title");
      row.append($("<td/>").text("expansion"));
      row.append($("<td/>").text("cached"));
      row.append($("<td/>").text("description"));
      row.append($("<td/>").text("parameters"));
      table.append(row);
      var property;
      for (property in expansionConfig) {
        if (expansionConfig.hasOwnProperty(property)) {
          row = $("<tr/>");
          row.append($("<td/>").text(property));
          row.append($("<td/>").text(expansionConfig[property].cached ? "true" : "false"));
          row.append($("<td/>").text(expansionConfig[property].description));
          var parameters = $("<td/>");
          if (expansionConfig[property].parameters) {
            var tableParameters = $("<table/>");
            for (parameter in expansionConfig[property].parameters) {
              tableParameters.append($("<tr/>").append($("<td/>").text(parameter)).append($("<td/>").text(expansionConfig[property].parameters[parameter])));
            }
            parameters.append(tableParameters);
          }
          row.append(parameters);
          table.append(row);
        }
      }
      var html = table.clone().wrap("<div>").parent().html();
      text = text.replace(/::expansionTable::/g, html);
    }
    if (text.match(/::configurationTableShort::/)) {
      var table = $("<table/>").addClass("table");
      var row = $("<tr/>").addClass("title");
      row.append($("<td/>").text("configuration"));
      row.append($("<td/>").text("uniqueKey"));
      row.append($("<td/>").text("fields"));
      row.append($("<td/>").text("dynamicFields"));
      row.append($("<td/>").text("mtas"));
      row.append($("<td/>").text("cql-parser"));
      row.append($("<td/>").text("join-parser"));
      table.append(row);
      for ( var property in solrConfig) {
        if (solrConfig.hasOwnProperty(property)) {
          row = $("<tr/>");
          row.append($("<td/>").text(property));
          row.append($("<td/>").text(solrConfig[property].uniqueKey));
          row.append($("<td/>").addClass("centered").text(solrConfig[property].fields.length));
          row.append($("<td/>").addClass("centered").text(solrConfig[property].dynamicFields.length));
          row.append($("<td/>").addClass("centered").text(solrConfig[property].mtas.length));
          row.append($("<td/>").addClass("centered").text(solrConfig[property].queryParserCql));
          row.append($("<td/>").addClass("centered").text(solrConfig[property].queryParserJoin));
          table.append(row);
        }
      }
      var html = table.clone().wrap("<div>").parent().html();
      text = text.replace(/::configurationTableShort::/g, html);
    }
    if (text.match(/::configurationTableLong::/)) {
      var table = $("<table/>").addClass("table");
      var row = $("<tr/>").addClass("title");
      row.append($("<td/>").text("configuration"));
      row.append($("<td/>").text("fields"));
      row.append($("<td/>").text("uniqueKey"));
      row.append($("<td/>").text("dynamic"));
      row.append($("<td/>").text("multiValued"));
      row.append($("<td/>").text("indexed"));
      row.append($("<td/>").text("required"));
      row.append($("<td/>").text("stored"));
      row.append($("<td/>").text("mtas"));
      row.append($("<td/>").text("type"));
      table.append(row);
      for ( var property in solrConfig) {
        if (solrConfig.hasOwnProperty(property)) {
          var numberOfFields = solrConfig[property].fields.length;
          var numberOfDynamicFields = solrConfig[property].dynamicFields.length;
          function progressItem(list, number, row, dynamic) {
            row.append($("<td/>").text(list[i]));
            row.append($("<td/>").addClass("centered").text((list[i] == solrConfig[property].uniqueKey) ? "*" : " "));
            row.append($("<td/>").addClass("centered").text(dynamic ? "*" : " "));
            row.append($("<td/>").addClass("centered").text(($.inArray(list[i], solrConfig[property].multiValued) >= 0) ? "*" : " "));
            row.append($("<td/>").addClass("centered").text(($.inArray(list[i], solrConfig[property].indexed) >= 0) ? "*" : " "));
            row.append($("<td/>").addClass("centered").text(($.inArray(list[i], solrConfig[property].required) >= 0) ? "*" : " "));
            row.append($("<td/>").addClass("centered").text(($.inArray(list[i], solrConfig[property].stored) >= 0) ? "*" : " "));
            row.append($("<td/>").addClass("centered").text(($.inArray(list[i], solrConfig[property].mtas) >= 0) ? "*" : " "));
            var type = [];
            if ($.inArray(list[i], solrConfig[property].typeText) >= 0) {
              type.push("text");
            } else if ($.inArray(list[i], solrConfig[property].typeString) >= 0) {
              type.push("string");
            } else if ($.inArray(list[i], solrConfig[property].typeBoolean) >= 0) {
              type.push("boolean");
            } else if ($.inArray(list[i], solrConfig[property].typeInteger) >= 0) {
              type.push("integer");
            } else if ($.inArray(list[i], solrConfig[property].typeLong) >= 0) {
              type.push("long");
            } else if ($.inArray(list[i], solrConfig[property].typeDate) >= 0) {
              type.push("date");
            } else if ($.inArray(list[i], solrConfig[property].typeBinary) >= 0) {
              type.push("binary");
            }
            row.append($("<td/>").text(type.join(", ")));
          }
          for (var i = 0; i < numberOfFields; i++) {
            row = $("<tr/>");
            if (i == 0) {
              row.append($("<td/>").text(property).attr("rowspan", (numberOfFields + numberOfDynamicFields)));
            }
            progressItem(solrConfig[property].fields, i, row, false);
            table.append(row);
          }
          for (var i = 0; i < numberOfDynamicFields; i++) {
            row = $("<tr/>");
            if (i == 0 && numberOfFields == 0) {
              row.append($("<td/>").text(property).attr("rowspan", numberOfDynamicFields));
            }
            progressItem(solrConfig[property].dynamicFields, i, row, true);
            table.append(row);
          }

        }
      }
      var html = table.clone().wrap("<div>").parent().html();
      text = text.replace(/::configurationTableLong::/g, html);
    }
    if (text.match(/::[^:\(]+\(([^\)]+)\)::/)) {
      text = text.replace(/::([^:\(]+)\(([^\)]+)\)::/g, function(match, p1, p2) {
        console.log(p1);
        return p2;
      });
    }
    return text;
  }
  function createCollapsable(name, open) {
    var item = $("<div/>").addClass("request").addClass("collapsable").addClass(name);
    if (open === true) {
      item.addClass("open");
    }
    var itemDescription = $("<div/>").addClass("description");
    itemDescription.append($("<div/>").addClass("descriptionStatus"));
    itemDescription.append($("<div/>").addClass("descriptionTitle"));
    itemDescription.append($("<div/>").addClass("descriptionSeparator"));
    itemDescription.append($("<div/>").addClass("descriptionInfo"));
    item.append(itemDescription);
    item.append($("<div/>").addClass("text"));
    return item;
  }
  function updateCollapsable(item, data) {
    if (item.hasClass("collapsable") && data !== null) {
      var itemDescriptionSeparator = item.find("div.descriptionSeparator");
      var itemDescriptionInfo = item.find("div.descriptionInfo");
      var itemText = item.find("div.text");
      if (data.data !== undefined && data.data !== null) {
        if (jQuery.type(data.data) == "string") {
          itemText.text(data.data);
        } else {
          for ( var key in data.data) {
            if (data.data.hasOwnProperty(key)) {
              if (data.data[key] != null) {
                var textItemList = itemText.find("div[data-key=" + key + "]");
                if (textItemList.length) {
                  textItemList.text(data.data[key]);
                } else {
                  itemText.append($("<div/>").text(data.data[key]).attr("data-key", key));
                }
              } else {
                itemText.find("div[data-key=" + key + "]").remove();
              }
            }
          }
        }
      }
      if (data.description !== undefined) {
        if (data.description !== null) {
          itemDescriptionSeparator.show();
          itemDescriptionInfo.text(data.description);
        } else {
          itemDescriptionSeparator.hide();
          itemDescriptionInfo.text("");
        }
      } else {
        var length = itemText.find("div").length;
        if (length > 0) {
          itemDescriptionSeparator.show();
          itemDescriptionInfo.text(length + " x");
        } else {
          itemDescriptionSeparator.hide();
          itemDescriptionInfo.text("");
        }
      }

    }
  }
  function updateTextarea(textarea, followRememberedSettings) {
    var text = textarea.val();
    var container = textarea.closest("div.block").parent();
    textarea.removeClass("badjson");
    try {
      if ($.trim(text) != "") {
        var obj = JSON.parse(text);
        var remember = textarea.parent().find("input[data-request=remember]").prop("checked");
        if (remember && followRememberedSettings) {
          var rememberedValueCache = getCookie("brokerCache");
          if (rememberedValueCache != null) {
            if (rememberedValueCache === "disabled") {
              if (obj.cache !== false) {
                obj.cache = false;
                textarea.val(JSON.stringify(obj, null, 2));
              }
            } else if (rememberedValueCache === "enabled") {
              if (obj.cache !== true && obj.cache !== undefined) {
                delete obj.cache;
                textarea.val(JSON.stringify(obj, null, 2));
              }
            }
          }
        }
        if (obj.cache === true || obj.cache === undefined) {
          textarea.parent().find("button[data-request=cache]").addClass("enabled");
          container.data("defaultCache", true);
          if (remember) {
            storeCookie("brokerCache", "enabled");
          } else {
            deleteCookie("brokerCache");
          }
        } else {
          textarea.parent().find("button[data-request=cache]").removeClass("enabled");
          container.data("defaultCache", false);
          if (remember) {
            storeCookie("brokerCache", "disabled");
          } else {
            deleteCookie("brokerCache");
          }
        }
        if (container.data("defaultStatus") !== undefined && container.data("defaultStatus") == true) {
          textarea.parent().find("button[data-request=status]").addClass("enabled");
          if (remember) {
            storeCookie("brokerStatus", "enabled");
          } else {
            deleteCookie("brokerStatus");
          }
        } else {
          textarea.parent().find("button[data-request=status]").removeClass("enabled");
          if (remember) {
            storeCookie("brokerStatus", "disabled");
          } else {
            deleteCookie("brokerStatus");
          }
        }
        textarea.parent().find(".json").show();
      } else {
        textarea.parent().find(".json").hide();
      }
    } catch (e) {
      textarea.addClass("badjson");
      textarea.parent().find(".json").hide();
    }
  }
  function updateEditor(textarea, type, value) {
    var text = textarea.val();
    var container = textarea.closest("div.block").parent();
    try {
      if (text.trim() != "") {
        var obj = null;
        if (type === "cache") {
          obj = JSON.parse(text);
          if (value == false) {
            container.data("defaultCache", false);
            obj.cache = false;
          } else if (obj.cache !== undefined) {
            container.data("defaultCache", true);
            delete obj.cache;
          }
        } else if (type === "status") {
          if (value == true) {
            container.data("defaultStatus", true);
          } else {
            container.data("defaultStatus", false);
          }
        }
        if (obj != null) {
          text = JSON.stringify(obj, null, 2);
          textarea.val(text);
        }
        updateTextarea(textarea, false);
      }
    } catch (e) {
      // do nothing
    }
  }
  function alignJson(textarea) {
    var text = textarea.val();
    try {
      if (text.trim() != "") {
        var obj = JSON.parse(text);
        text = JSON.stringify(obj, null, 2);
        textarea.val(text);
      }
    } catch (e) {
      alert(e.message);
    }
  }

  function resetRequestResponse(container) {
    // update data
    container.removeData("requestStatusId");
    container.removeData("requestStatusKey");
    // abort running request
    if (container.data("requestStatusCreate") !== undefined) {
      container.data("requestStatusCreate").abort();
      container.removeData("requestStatusCreate");
    }
    if (container.data("requestStatusStart") !== undefined) {
      container.data("requestStatusStart").abort();
      container.removeData("requestStatusStart");
    }
    if (container.data("requestStatusUpdate") !== undefined) {
      container.data("requestStatusUpdate").abort();
      container.removeData("requestStatusUpdate");
    }
    // clean up divs and make collapsable work
    container.find("div.collapsable div.text").text("");
    container.find("div.collapsable div.descriptionSeparator").text(":").hide();
    container.find("div.collapsable div.descriptionInfo").text("");
    container.find("div.status div.title").text("Request").hide();
    container.find("div.status div.request").hide();
    container.find("div.status div.request.broker div.descriptionTitle").text("Request Broker");
    container.find("div.status div.request.solr div.descriptionTitle").text("Request Solr");
    container.find("div.status div.request.warnings div.descriptionTitle").text("Warnings");
    container.find("div.status div.request.errors div.descriptionTitle").text("Errors");
    container.find("div.status div.request.info div.descriptionTitle").text("Info");
    container.find("div.response div.content div.descriptionTitle").text("Response");
    container.find("div.status div.progress").text("").hide();
    container.find("div.response div.title").text("Response").hide();
    container.find("div.response div.content").hide().find("div.descriptionTitle").text("Response");
    container.find("div.response div.error").hide().find("div.descriptionTitle").text("Error");
    // collapsables
    container.find("div.collapsable").each(function() {
      if ($(this).hasClass("open")) {
        $(this).find("div.text").show();
        $(this).find("div.description div.descriptionStatus").html("&#x25BC;");
      } else {
        $(this).find("div.text").hide();
        $(this).find("div.description div.descriptionStatus").html("&#x25B6;");
      }
    });
    container.find("div.collapsable div.description").off("click").click(function() {
      var textDiv = $(this).parent().find("div.text");
      var status = textDiv.is(":visible") ? "&#x25B6;" : "&#x25BC;";
      $(this).find("div.descriptionStatus").html(status);
      textDiv.slideToggle("fast");
    });
  }
  function doUpdateProgress(container, id, key, startTime) {
    if (container.data("requestStatusId") !== undefined && container.data("requestStatusKey") !== undefined) {
      if (container.data("requestStatusId") == id && container.data("requestStatusKey") == key) {
        var currentTime = new Date().getTime();
        var totalTime = currentTime - startTime;
        container.find("div.status div.progress").text("waiting (" + parseInt(totalTime).toLocaleString("en-UK") + " ms)");
        setTimeout(function() {
          doUpdateProgress(container, id, key, startTime);
        }, 100);
      }
    }
  }
  function doRequest(textarea, status) {
    var text = textarea.val();
    var container = textarea.closest("div.block").parent();
    resetRequestResponse(container);
    container.find("div.examples").hide();
    // do request
    if ($.trim(text) != "") {
      container.find("div.status div.title").show();
      container.find("div.status div.request.broker").show().find("div.text").text(text);
      location.hash = container.data("id") + "-status";
      if (status) {
        container.data("requestStatusCreate", $.ajax({
          "type" : "POST",
          "url" : container.data("statuscreateurl"),
          "dataType" : "text",
          "data" : text,
          "contentType" : "application/json",
          "success" : function(data) {
            container.find("div.status div.progress").text("").hide();
            try {
              var obj = JSON.parse(data);
              if (obj.brokerWarnings !== undefined) {
                updateCollapsable(container.find("div.status div.request.warnings").show(), obj.brokerWarnings);
              }
              if (obj.brokerErrors !== undefined) {
                updateCollapsable(container.find("div.status div.request.errors").show(), obj.brokerErrors);
              }
              if (obj.solrRequest !== undefined) {
                updateCollapsable(container.find("div.status div.request.solr").show(), obj.solrRequest);
              }
              if (obj.solrStatus !== undefined) {
                updateCollapsable(container.find("div.status div.request.info").show(), obj.solrStatus);
              }
              if (obj.status !== undefined && obj.status == "OK" && obj.id !== undefined && obj.key !== undefined) {
                container.data("requestStatusId", obj.id);
                container.data("requestStatusKey", obj.key);
                doRequestStatusStart(container, obj.id, obj.key);
                doRequestStatusUpdate(container, obj.id, obj.key, true);
              } else if (obj.status !== undefined && obj.status == "ERROR") {
                location.hash = container.data("id") + "-status";
              } else {
                alert("Create new status : " + data);
              }
            } catch (err) {
              alert("Create new status: " + err + " - " + data);
            }
          },
          "error" : function(jqXHR, textStatus, errorThrown) {
            console.log("Create new status: " + jqXHR.statusText);
          },
          "complete" : function(jqXHR, textStatus) {
            container.removeData("requestStatusCreate");
          }
        }));
      } else {
        var id = "id" + Math.random().toString(36).substring(7);
        var key = "key" + Math.random().toString(36).substring(7);
        var startTime = new Date().getTime();
        container.find("div.status div.title").show();
        container.find("div.status div.progress").show();
        container.data("requestStatusId", id);
        container.data("requestStatusKey", key);
        doUpdateProgress(container, id, key, startTime);
        container.data("requestSearch", $.ajax({
          "type" : "POST",
          "url" : container.data("searchurl"),
          "dataType" : "text",
          "data" : text,
          "contentType" : "application/json",
          "success" : function(data, requestStatus, xhr) {
            container.find("div.response div.title").show();
            var contentObject = container.find("div.response div.content").show();
            try {
              var obj = JSON.parse(data);
              var totalTime = new Date().getTime() - startTime;
              var description = "successfully finished in " + parseInt(totalTime).toLocaleString("en-UK") + " ms";
              var details = {};
              var warnings = xhr.getResponseHeader("X-Broker-warnings");
              if (warnings != null) {
                warnings = parseInt(warnings);
                if (warnings > 0) {
                  updateCollapsable(container.find("div.status div.request.warnings").show(), {
                    "description" : warnings + " " + ((warnings > 1) ? "warnings" : "warning"),
                    "data" : {
                      "broker" : warnings + " " + ((warnings > 1) ? "warnings" : "warning") + " from parsing this request by broker"
                    }
                  });
                }
              }
              details.broker = "request successfully parsed by broker";
              var configuration = xhr.getResponseHeader("X-Broker-configuration");
              if (configuration != null) {
                details.config = "solr configuration '" + configuration + "' used";
              }
              var shards = xhr.getResponseHeader("X-Broker-shards");
              if (shards != null) {
                shards = parseInt(shards);
                details.shards = "request for " + shards + " " + ((shards > 1) ? "shards" : "shard");
              }
              details.solr = "successfully finished solr request in " + parseInt(totalTime).toLocaleString("en-UK") + " ms";
              updateCollapsable(container.find("div.status div.request.info").show(), {
                "description" : description,
                "data" : details
              });
              contentObject.show().find("div.text").html(
                  "<pre>" + JSON.stringify(obj, undefined, 2).replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;") + "</pre>");
            } catch (err) {
              contentObject.find("div.text").text(data);
            }
          },
          "error" : function(jqXHR, textStatus, errorThrown) {
            if (container.data("requestStatusId") !== undefined && container.data("requestStatusId") == id) {
              var errorObject = container.find("div.response div.error");
              try {
                var obj = JSON.parse(jqXHR.responseText);
                var totalTime = new Date().getTime() - startTime;
                if (obj.brokerWarnings !== undefined) {
                  updateCollapsable(container.find("div.status div.request.warnings").show(), obj.brokerWarnings);
                }
                if (obj.brokerErrors !== undefined) {
                  updateCollapsable(container.find("div.status div.request.errors").show(), obj.brokerErrors);
                } else {
                  var errorText = "error in solr response: " + jqXHR.statusText + " (" + jqXHR.status + ")";
                  updateCollapsable(container.find("div.status div.request.errors").show(), {
                    "data" : {
                      "solr" : errorText
                    }
                  });
                }
                if (obj.error !== undefined) {
                  container.find("div.response div.title").show();
                  try {
                    errorObject.show().find("div.text").html(
                        "<pre>" + JSON.stringify(obj.error, undefined, 2).replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;") + "</pre>");
                  } catch (err) {
                    errorObject.show().find("div.text").html("").append($("<pre/>").text(obj.error));
                  }
                }
              } catch (err) {
                try {
                  var obj = JSON.parse(jqXHR.responseText);
                  errorObject.show().find("div.text").html(
                      "<pre>" + JSON.stringify(obj, undefined, 2).replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;") + "</pre>");
                } catch (err) {
                  errorObject.show().find("div.text").html("").append($("<pre/>").text(jqXHR.responseText));
                }
              }
              updateCollapsable(container.find("div.status div.request.info").show(), {
                "description" : "error in solr response",
                "data" : {
                  "solr" : "finished with error in solr response (" + jqXHR.status + ")"
                }
              });
            }
          },
          "complete" : function(jqXHR, textStatus) {
            location.hash = container.data("id") + "-response";
            container.find("div.status div.progress").hide();
            container.removeData("requestSearch");
            container.removeData("requestStatusId");
            container.removeData("requestStatusKey");
          }
        }));
      }
    } else {
      alert("Create new status: no request");
    }
  }
  function doRequestStatusStart(container, id, key) {
    if (container.data("requestStatusId") !== undefined && container.data("requestStatusKey") !== undefined) {
      if (container.data("requestStatusId") == id && container.data("requestStatusKey") == key) {
        var startTime = new Date().getTime();
        container.find("div.status div.title").show();
        container.find("div.status div.progress").show();
        doUpdateProgress(container, id, key, startTime);
        location.hash = container.data("id") + "-status";
        container.data("requestStatusStart", $.ajax({
          "type" : "POST",
          "url" : container.data("statusstarturl"),
          "dataType" : "text",
          "data" : "{\"id\": \"" + id + "\", \"key\": \"" + key + "\"}",
          "contentType" : "application/json",
          "success" : function(data) {
            container.find("div.response div.title").show();
            var contentObject = container.find("div.response div.content").show();
            try {
              var obj = JSON.parse(data);
              var totalTime = new Date().getTime() - startTime;
              updateCollapsable(container.find("div.status div.request.info").show(), {
                "description" : "successfully finished in " + parseInt(totalTime).toLocaleString("en-UK") + " ms",
                "data" : {
                  "solr" : "successfully finished solr request in " + parseInt(totalTime).toLocaleString("en-UK") + " ms"
                }
              });
              contentObject.show().find("div.text").html(
                  "<pre>" + JSON.stringify(obj, undefined, 2).replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;") + "</pre>");
            } catch (err) {
              contentObject.find("div.text").text(data);
            }
          },
          "error" : function(jqXHR, textStatus, errorThrown) {
            // only if still current request
            if (container.data("requestStatusId") !== undefined && container.data("requestStatusId") == id) {
              var errorText = "error in solr response: " + jqXHR.statusText + " (" + jqXHR.status + ")";
              updateCollapsable(container.find("div.status div.request.errors").show(), {
                "data" : {
                  "solr" : errorText
                }
              });
              updateCollapsable(container.find("div.status div.request.info").show(), {
                "description" : "error in solr response",
                "data" : {
                  "solr" : "finished with error in solr response (" + jqXHR.status + ")"
                }
              });
              var errorObject = container.find("div.response div.error").show();
              container.find("div.response div.title").show();
              try {
                var obj = JSON.parse(jqXHR.responseText);
                errorObject.show().find("div.text").html(
                    "<pre>" + JSON.stringify(obj, undefined, 2).replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;") + "</pre>");
              } catch (err) {
                errorObject.find("div.text").text("").append($("<pre/>").text(jqXHR.responseText));
              }
            }
          },
          "complete" : function(jqXHR, textStatus) {
            container.find("div.status div.progress").hide();
            container.removeData("requestStatusStart");
            container.removeData("requestStatusId");
            container.removeData("requestStatusKey");
            if (container.data("requestStatusUpdate") !== undefined) {
              container.data("requestStatusUpdate").abort();
              container.removeData("requestStatusUpdate");
            }
            location.hash = container.data("id") + "-response";
          }
        }));
      }
    }
  }
  function doRequestStatusUpdate(container, id, key) {
    if (container.data("requestStatusId") !== undefined && container.data("requestStatusKey") !== undefined) {
      if (container.data("requestStatusId") == id && container.data("requestStatusKey") == key) {
        container.find("div.status div.title").show();
        container.data("requestStatusUpdate", $.ajax({
          "type" : "POST",
          "url" : container.data("statusupdateurl"),
          "dataType" : "text",
          "data" : "{\"id\": \"" + id + "\", \"key\": \"" + key + "\" }",
          "contentType" : "application/json",
          "success" : function(data) {
            try {
              var obj = JSON.parse(data);
              var timeStep = 1000;
              if (obj.solrStatus !== undefined) {
                updateCollapsable(container.find("div.status div.request.info").show(), obj.solrStatus);
              }
              container.removeData("requestStatusUpdate");
              // continue
              if (container.data("requestStatusId") !== undefined && container.data("requestStatusId") == id) {
                setTimeout(function() {
                  doRequestStatusUpdate(container, id, key);
                }, timeStep);
              }
            } catch (err) {
              alert("Create new status: " + err + " - " + data);
            }
          },
          "error" : function(jqXHR, textStatus, errorThrown) {
            container.removeData("requestStatusUpdate");
            if (container.data("requestStatusId") !== undefined && container.data("requestStatusId") == id) {
              console.log("Update status: " + jqXHR.statusText + " " + id);
            }
          }
        }));
      }
    }
  }
  function storeCookie(name, value) {
    var expires = new Date();
    expires.setTime(expires.getTime() + (1 * 24 * 60 * 60 * 1000));
    document.cookie = name + "=" + value + ";expires=" + expires.toUTCString();
  }
  function deleteCookie(name) {
    document.cookie = name + "=; expires=Thu, 01 Jan 1970 00:00:00 UTC;";
  }
  function getCookie(name) {
    var value = document.cookie.match('(^|;) ?' + name + '=([^;]*)(;|$)');
    return value ? value[2] : null;
  }

});
