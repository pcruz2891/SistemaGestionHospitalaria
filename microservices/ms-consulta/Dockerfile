# --- Etapa 1: compilación con Maven ---
FROM maven:3.9-eclipse-temurin-17 AS builder

WORKDIR /app

# Copiar primero el pom.xml para aprovechar la caché de dependencias de Docker
COPY pom.xml .
COPY .mvn .mvn
COPY mvnw .
RUN mvn dependency:go-offline -B

# Copiar el resto del código y construir el .jar (sin correr tests, ya probados antes)
COPY src ./src
RUN mvn clean package -DskipTests -B

# --- Etapa 2: imagen final, solo con el JRE ---
FROM eclipse-temurin:17-jre-alpine

WORKDIR /app

COPY --from=builder /app/target/*.jar app.jar

EXPOSE 8082

ENTRYPOINT ["java", "-jar", "app.jar"]