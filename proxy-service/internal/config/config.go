package config

import (
	"os"
	"strconv"
	"strings"
	"time"
)

type Config struct {
	Port           string
	ProxyList      []string
	CooldownPeriod time.Duration
}

const (
	defaultPort           = "8081"
	defaultCooldownSecond = 60
)

func Load() Config {
	return Config{
		Port:           envOrDefault("PORT", defaultPort),
		ProxyList:      parseProxyList(os.Getenv("PROXY_LIST")),
		CooldownPeriod: time.Duration(envIntOrDefault("PROXY_COOLDOWN_SECONDS", defaultCooldownSecond)) * time.Second,
	}
}

func envOrDefault(key, fallback string) string {
	if v := os.Getenv(key); v != "" {
		return v
	}
	return fallback
}

func envIntOrDefault(key string, fallback int) int {
	v := os.Getenv(key)
	if v == "" {
		return fallback
	}
	parsed, err := strconv.Atoi(v)
	if err != nil {
		return fallback
	}
	return parsed
}

func parseProxyList(raw string) []string {
	if raw == "" {
		return nil
	}
	parts := strings.Split(raw, ",")
	proxies := make([]string, 0, len(parts))
	for _, p := range parts {
		p = strings.TrimSpace(p)
		if p != "" {
			proxies = append(proxies, p)
		}
	}
	return proxies
}
