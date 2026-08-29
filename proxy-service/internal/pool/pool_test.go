package pool

import (
	"errors"
	"testing"
	"time"
)

func TestNext_RoundRobinsThroughAllProxies(t *testing.T) {
	p := New([]string{"proxy-a", "proxy-b", "proxy-c"}, time.Minute)

	got := make([]string, 0, 6)
	for i := 0; i < 6; i++ {
		proxy, err := p.Next()
		if err != nil {
			t.Fatalf("Next() returned unexpected error: %v", err)
		}
		got = append(got, proxy)
	}

	want := []string{"proxy-a", "proxy-b", "proxy-c", "proxy-a", "proxy-b", "proxy-c"}
	for i := range want {
		if got[i] != want[i] {
			t.Fatalf("call %d: got %q, want %q (full sequence: %v)", i, got[i], want[i], got)
		}
	}
}

func TestNext_EmptyPoolReturnsErrNoProxiesConfigured(t *testing.T) {
	p := New(nil, time.Minute)

	_, err := p.Next()
	if !errors.Is(err, ErrNoProxiesConfigured) {
		t.Fatalf("got error %v, want ErrNoProxiesConfigured", err)
	}
}

func TestNext_DeduplicatesConfiguredProxies(t *testing.T) {
	p := New([]string{"proxy-a", "proxy-a", "proxy-b"}, time.Minute)

	if got := p.Size(); got != 2 {
		t.Fatalf("Size() = %d, want 2 after deduplication", got)
	}
}

func TestReport_FailureExcludesProxyUntilCooldownElapses(t *testing.T) {
	p := New([]string{"proxy-a", "proxy-b"}, time.Minute)
	fakeNow := time.Now()
	p.now = func() time.Time { return fakeNow }

	if err := p.Report("proxy-a", false); err != nil {
		t.Fatalf("Report() returned unexpected error: %v", err)
	}

	for i := 0; i < 3; i++ {
		got, err := p.Next()
		if err != nil {
			t.Fatalf("Next() returned unexpected error: %v", err)
		}
		if got != "proxy-b" {
			t.Fatalf("Next() = %q, want %q while proxy-a is in cooldown", got, "proxy-b")
		}
	}

	fakeNow = fakeNow.Add(time.Minute + time.Second)
	sawA := false
	for i := 0; i < 2; i++ {
		got, err := p.Next()
		if err != nil {
			t.Fatalf("Next() returned unexpected error: %v", err)
		}
		if got == "proxy-a" {
			sawA = true
		}
	}
	if !sawA {
		t.Fatal("expected proxy-a to be eligible again after its cooldown elapsed")
	}
}

func TestReport_SuccessClearsCooldown(t *testing.T) {
	p := New([]string{"proxy-a", "proxy-b"}, time.Hour)
	fakeNow := time.Now()
	p.now = func() time.Time { return fakeNow }

	if err := p.Report("proxy-a", false); err != nil {
		t.Fatalf("Report() returned unexpected error: %v", err)
	}
	if err := p.Report("proxy-a", true); err != nil {
		t.Fatalf("Report() returned unexpected error: %v", err)
	}

	got, err := p.Next()
	if err != nil {
		t.Fatalf("Next() returned unexpected error: %v", err)
	}
	if got != "proxy-a" {
		t.Fatalf("Next() = %q, want %q immediately after a successful report clears cooldown", got, "proxy-a")
	}
}

func TestReport_UnknownProxyReturnsError(t *testing.T) {
	p := New([]string{"proxy-a"}, time.Minute)

	err := p.Report("proxy-does-not-exist", false)
	if !errors.Is(err, ErrUnknownProxy) {
		t.Fatalf("got error %v, want ErrUnknownProxy", err)
	}
}

func TestNext_AllProxiesInCooldownReturnsErrNoProxiesAvailable(t *testing.T) {
	p := New([]string{"proxy-a", "proxy-b"}, time.Minute)
	fakeNow := time.Now()
	p.now = func() time.Time { return fakeNow }

	if err := p.Report("proxy-a", false); err != nil {
		t.Fatalf("Report() returned unexpected error: %v", err)
	}
	if err := p.Report("proxy-b", false); err != nil {
		t.Fatalf("Report() returned unexpected error: %v", err)
	}

	_, err := p.Next()
	if !errors.Is(err, ErrNoProxiesAvailable) {
		t.Fatalf("got error %v, want ErrNoProxiesAvailable", err)
	}
}
