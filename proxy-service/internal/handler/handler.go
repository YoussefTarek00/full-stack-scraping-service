package handler

import (
	"encoding/json"
	"errors"
	"log"
	"net/http"

	"github.com/ytarek-WCFY/full-stack-scraping-service/proxy-service/internal/pool"
)

type Rotator interface {
	Next() (string, error)
	Report(proxyURL string, success bool) error
}

type Handler struct {
	rotator Rotator
}

func New(rotator Rotator) *Handler {
	return &Handler{rotator: rotator}
}

func (h *Handler) Routes() http.Handler {
	mux := http.NewServeMux()
	mux.HandleFunc("GET /health", h.health)
	mux.HandleFunc("GET /proxy/next", h.next)
	mux.HandleFunc("POST /proxy/report", h.report)
	return mux
}

type nextResponse struct {
	Proxy string `json:"proxy"`
}

type reportRequest struct {
	Proxy   string `json:"proxy"`
	Success bool   `json:"success"`
}

type errorResponse struct {
	Error string `json:"error"`
}

func (h *Handler) health(w http.ResponseWriter, r *http.Request) {
	writeJSON(w, http.StatusOK, map[string]string{"status": "ok"})
}

func (h *Handler) next(w http.ResponseWriter, r *http.Request) {
	proxy, err := h.rotator.Next()
	if err != nil {
		switch {
		case errors.Is(err, pool.ErrNoProxiesConfigured), errors.Is(err, pool.ErrNoProxiesAvailable):
			writeJSON(w, http.StatusServiceUnavailable, errorResponse{Error: err.Error()})
		default:
			log.Printf("proxy-service: unexpected error from Next(): %v", err)
			writeJSON(w, http.StatusInternalServerError, errorResponse{Error: "internal error"})
		}
		return
	}
	writeJSON(w, http.StatusOK, nextResponse{Proxy: proxy})
}

func (h *Handler) report(w http.ResponseWriter, r *http.Request) {
	var req reportRequest
	if err := json.NewDecoder(r.Body).Decode(&req); err != nil {
		writeJSON(w, http.StatusBadRequest, errorResponse{Error: "invalid request body"})
		return
	}
	if req.Proxy == "" {
		writeJSON(w, http.StatusBadRequest, errorResponse{Error: "proxy field is required"})
		return
	}

	if err := h.rotator.Report(req.Proxy, req.Success); err != nil {
		switch {
		case errors.Is(err, pool.ErrUnknownProxy):
			writeJSON(w, http.StatusNotFound, errorResponse{Error: err.Error()})
		default:
			log.Printf("proxy-service: unexpected error from Report(): %v", err)
			writeJSON(w, http.StatusInternalServerError, errorResponse{Error: "internal error"})
		}
		return
	}
	w.WriteHeader(http.StatusNoContent)
}

func writeJSON(w http.ResponseWriter, status int, body any) {
	w.Header().Set("Content-Type", "application/json")
	w.WriteHeader(status)
	if err := json.NewEncoder(w).Encode(body); err != nil {
		log.Printf("proxy-service: failed to encode response: %v", err)
	}
}
