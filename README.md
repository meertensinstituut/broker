# Broker

The broker provides an easy configurable JSON interface translating JSON into Solr requests with support for caching and query expansion. Support for [Mtas](https://meertensinstituut.github.io/mtas/) is integrated in the Broker. See [meertensinstituut.github.io/broker/](https://meertensinstituut.github.io/broker/) for more documentation and instructions.

A Docker image is available. To build and run

```console
docker build -t broker https://raw.githubusercontent.com/meertensinstituut/broker/master/docker/Dockerfile
docker run -t -i -p 8080:80 --name broker broker
```

This will provide a website on port 8080 on the ip of your docker host with a running broker.

