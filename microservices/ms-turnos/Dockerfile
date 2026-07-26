# --- Etapa 1: compilación ---
FROM golang:1.26-alpine AS builder

WORKDIR /app

# Copiar primero los archivos de dependencias para aprovechar la caché de Docker
COPY go.mod go.sum ./
RUN go mod download

# Copiar el resto del código y compilar el binario
COPY . .
RUN CGO_ENABLED=0 GOOS=linux go build -o ms-turnos .

# --- Etapa 2: imagen final, ligera ---
FROM alpine:3.20

WORKDIR /app

# Certificados TLS por si el servicio necesita hacer llamadas HTTPS salientes
RUN apk add --no-cache ca-certificates

COPY --from=builder /app/ms-turnos .

EXPOSE 8081

CMD ["./ms-turnos"]