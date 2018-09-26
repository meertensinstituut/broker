# Broker

The broker provides an easy configurable JSON interface translating JSON into Solr requests with support for caching and query expansion. Support for [Mtas](https://meertensinstituut.github.io/mtas/) is integrated in the Broker. See [meertensinstituut.github.io/broker/](https://meertensinstituut.github.io/broker/) for more documentation and instructions.

A [docker](https://hub.docker.com/r/meertensinstituut/broker/) image is available. To build and run

```console
docker build -t broker https://raw.githubusercontent.com/meertensinstituut/broker/master/docker/Dockerfile
docker run -t -i -p 8080:80 --name broker broker
```

This will provide a website on port 8080 on the ip of your docker host with a running Broker.

# Copyright and license

Copyright 2017-2018 Koninklijke Nederlandse Academie van Wetenschappen.

Licensed under the Apache License, Version 2.0 (the "License");
you may not use this file except in compliance with the License.
You may obtain a copy of the License at

    http://www.apache.org/licenses/LICENSE-2.0

Unless required by applicable law or agreed to in writing, software
distributed under the License is distributed on an "AS IS" BASIS,
WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
See the License for the specific language governing permissions and
limitations under the License.
