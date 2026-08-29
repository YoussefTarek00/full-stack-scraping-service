FROM golang:1.22-alpine AS builder
WORKDIR /src
COPY go.mod ./
COPY . .
RUN CGO_ENABLED=0 go build -o /out/proxy-service .

FROM alpine:3.20
RUN adduser -D -H appuser
COPY --from=builder /out/proxy-service /usr/local/bin/proxy-service
USER appuser
EXPOSE 8081
ENTRYPOINT ["proxy-service"]
