package main

import (
	"context"
	"errors"
	"log"
	"net/http"
	"os/signal"
	"syscall"
	"time"

	"github.com/ytarek-WCFY/full-stack-scraping-service/proxy-service/internal/config"
	"github.com/ytarek-WCFY/full-stack-scraping-service/proxy-service/internal/handler"
	"github.com/ytarek-WCFY/full-stack-scraping-service/proxy-service/internal/pool"
)

func main() {
	cfg := config.Load()

	proxyPool := pool.New(cfg.ProxyList, cfg.CooldownPeriod)
	if proxyPool.Size() == 0 {
		log.Println("proxy-service: WARNING starting with an empty proxy pool; every /proxy/next call will return 503 until PROXY_LIST is configured")
	}

	srv := &http.Server{
		Addr:    ":" + cfg.Port,
		Handler: handler.New(proxyPool).Routes(),
	}

	ctx, stop := signal.NotifyContext(context.Background(), syscall.SIGINT, syscall.SIGTERM)
	defer stop()

	go func() {
		log.Printf("proxy-service: listening on %s with %d configured proxies", srv.Addr, proxyPool.Size())
		if err := srv.ListenAndServe(); err != nil && !errors.Is(err, http.ErrServerClosed) {
			log.Fatalf("proxy-service: server error: %v", err)
		}
	}()

	<-ctx.Done()
	log.Println("proxy-service: shutting down")

	shutdownCtx, cancel := context.WithTimeout(context.Background(), 5*time.Second)
	defer cancel()
	if err := srv.Shutdown(shutdownCtx); err != nil {
		log.Fatalf("proxy-service: graceful shutdown failed: %v", err)
	}
}
