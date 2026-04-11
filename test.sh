exec ./cli \
  --app-config=./ro/private/config/quartz-cli.yaml \
  --engine-payload=quartz \
  --engine-method=loop \
  --url=http://dispatcher/dispatcher/collect-payloads
