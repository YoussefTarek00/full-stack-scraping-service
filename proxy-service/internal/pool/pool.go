package pool

import (
	"errors"
	"sync"
	"time"
)

var ErrNoProxiesConfigured = errors.New("pool: no proxies configured")
var ErrNoProxiesAvailable = errors.New("pool: no healthy proxies available")
var ErrUnknownProxy = errors.New("pool: unknown proxy")

type entry struct {
	url           string
	cooldownUntil time.Time
}

func (e *entry) isHealthy(now time.Time) bool {
	return now.After(e.cooldownUntil) || now.Equal(e.cooldownUntil)
}

type Pool struct {
	mu       sync.Mutex
	entries  []*entry
	next     int
	cooldown time.Duration
	now      func() time.Time
}

func New(proxyURLs []string, cooldown time.Duration) *Pool {
	seen := make(map[string]bool, len(proxyURLs))
	entries := make([]*entry, 0, len(proxyURLs))
	for _, u := range proxyURLs {
		if u == "" || seen[u] {
			continue
		}
		seen[u] = true
		entries = append(entries, &entry{url: u})
	}
	return &Pool{
		entries:  entries,
		cooldown: cooldown,
		now:      time.Now,
	}
}

func (p *Pool) Next() (string, error) {
	p.mu.Lock()
	defer p.mu.Unlock()

	if len(p.entries) == 0 {
		return "", ErrNoProxiesConfigured
	}

	now := p.now()
	for i := 0; i < len(p.entries); i++ {
		idx := (p.next + i) % len(p.entries)
		if p.entries[idx].isHealthy(now) {
			p.next = (idx + 1) % len(p.entries)
			return p.entries[idx].url, nil
		}
	}
	return "", ErrNoProxiesAvailable
}

func (p *Pool) Report(proxyURL string, success bool) error {
	p.mu.Lock()
	defer p.mu.Unlock()

	for _, e := range p.entries {
		if e.url != proxyURL {
			continue
		}
		if success {
			e.cooldownUntil = time.Time{}
		} else {
			e.cooldownUntil = p.now().Add(p.cooldown)
		}
		return nil
	}
	return ErrUnknownProxy
}

func (p *Pool) Size() int {
	p.mu.Lock()
	defer p.mu.Unlock()
	return len(p.entries)
}
