FROM uselagoon/solr-8-drupal:latest
COPY .gcp/solr /solr-conf/conf

CMD solr-recreate oafc /solr-conf && solr-foreground
