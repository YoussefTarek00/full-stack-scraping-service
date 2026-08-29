package handler

import (
	"bytes"
	"encoding/json"
	"errors"
	"net/http"
	"net/http/httptest"
	"testing"

	"github.com/ytarek-WCFY/full-stack-scraping-service/proxy-service/internal/pool"
)

type fakeRotator struct {
	nextProxy   string
	nextErr     error
	reportErr   error
	reportedURL string
	reportedOK  *bool
}

func (f *fakeRotator) Next() (string, error) {
	return f.nextProxy, f.nextErr
}

func (f *fakeRotator) Report(proxyURL string, success bool) error {
	f.reportedURL = proxyURL
	f.reportedOK = &success
	return f.reportErr
}

func TestHealth_ReturnsOK(t *testing.T) {
	h := New(&fakeRotator{})
	req := httptest.NewRequest(http.MethodGet, "/health", nil)
	rec := httptest.NewRecorder()

	h.Routes().ServeHTTP(rec, req)

	if rec.Code != http.StatusOK {
		t.Fatalf("status = %d, want %d", rec.Code, http.StatusOK)
	}
}

func TestNext_ReturnsProxyAsJSON(t *testing.T) {
	h := New(&fakeRotator{nextProxy: "http://10.0.0.1:8080"})
	req := httptest.NewRequest(http.MethodGet, "/proxy/next", nil)
	rec := httptest.NewRecorder()

	h.Routes().ServeHTTP(rec, req)

	if rec.Code != http.StatusOK {
		t.Fatalf("status = %d, want %d", rec.Code, http.StatusOK)
	}
	var body nextResponse
	if err := json.NewDecoder(rec.Body).Decode(&body); err != nil {
		t.Fatalf("failed to decode response body: %v", err)
	}
	if body.Proxy != "http://10.0.0.1:8080" {
		t.Fatalf("proxy = %q, want %q", body.Proxy, "http://10.0.0.1:8080")
	}
}

func TestNext_NoProxiesAvailableReturns503(t *testing.T) {
	h := New(&fakeRotator{nextErr: pool.ErrNoProxiesAvailable})
	req := httptest.NewRequest(http.MethodGet, "/proxy/next", nil)
	rec := httptest.NewRecorder()

	h.Routes().ServeHTTP(rec, req)

	if rec.Code != http.StatusServiceUnavailable {
		t.Fatalf("status = %d, want %d", rec.Code, http.StatusServiceUnavailable)
	}
}

func TestNext_UnexpectedErrorReturns500(t *testing.T) {
	h := New(&fakeRotator{nextErr: errors.New("boom")})
	req := httptest.NewRequest(http.MethodGet, "/proxy/next", nil)
	rec := httptest.NewRecorder()

	h.Routes().ServeHTTP(rec, req)

	if rec.Code != http.StatusInternalServerError {
		t.Fatalf("status = %d, want %d", rec.Code, http.StatusInternalServerError)
	}
}

func TestReport_ForwardsProxyAndSuccessToRotator(t *testing.T) {
	rotator := &fakeRotator{}
	h := New(rotator)
	body, _ := json.Marshal(reportRequest{Proxy: "http://10.0.0.1:8080", Success: false})
	req := httptest.NewRequest(http.MethodPost, "/proxy/report", bytes.NewReader(body))
	rec := httptest.NewRecorder()

	h.Routes().ServeHTTP(rec, req)

	if rec.Code != http.StatusNoContent {
		t.Fatalf("status = %d, want %d", rec.Code, http.StatusNoContent)
	}
	if rotator.reportedURL != "http://10.0.0.1:8080" {
		t.Fatalf("reportedURL = %q, want %q", rotator.reportedURL, "http://10.0.0.1:8080")
	}
	if rotator.reportedOK == nil || *rotator.reportedOK != false {
		t.Fatalf("reportedOK = %v, want false", rotator.reportedOK)
	}
}

func TestReport_MissingProxyFieldReturns400(t *testing.T) {
	h := New(&fakeRotator{})
	body, _ := json.Marshal(reportRequest{Success: true})
	req := httptest.NewRequest(http.MethodPost, "/proxy/report", bytes.NewReader(body))
	rec := httptest.NewRecorder()

	h.Routes().ServeHTTP(rec, req)

	if rec.Code != http.StatusBadRequest {
		t.Fatalf("status = %d, want %d", rec.Code, http.StatusBadRequest)
	}
}

func TestReport_InvalidJSONReturns400(t *testing.T) {
	h := New(&fakeRotator{})
	req := httptest.NewRequest(http.MethodPost, "/proxy/report", bytes.NewReader([]byte("not json")))
	rec := httptest.NewRecorder()

	h.Routes().ServeHTTP(rec, req)

	if rec.Code != http.StatusBadRequest {
		t.Fatalf("status = %d, want %d", rec.Code, http.StatusBadRequest)
	}
}

func TestReport_UnknownProxyReturns404(t *testing.T) {
	h := New(&fakeRotator{reportErr: pool.ErrUnknownProxy})
	body, _ := json.Marshal(reportRequest{Proxy: "http://ghost", Success: true})
	req := httptest.NewRequest(http.MethodPost, "/proxy/report", bytes.NewReader(body))
	rec := httptest.NewRecorder()

	h.Routes().ServeHTTP(rec, req)

	if rec.Code != http.StatusNotFound {
		t.Fatalf("status = %d, want %d", rec.Code, http.StatusNotFound)
	}
}
