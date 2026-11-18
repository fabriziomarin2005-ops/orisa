# 🛡️ ORISA — Intelligent HSE Inspection Platform

**ORISA** es una plataforma integral de gestión de seguridad industrial, salud ocupacional y medio ambiente (HSE). El sistema combina una interfaz web administrativa de alta reactividad con la recepción de telemetría y eventos en tiempo real provenientes de nodos de hardware IoT (ESP32-S3).

---

## 🚀 Características Principales

* **Monitoreo y Analíticas HSE:** Visualización centralizada de incidentes por zonas industriales y niveles de criticidad (*Bajo, Medio, Alto, Crítico*).
* **Integración IoT en Tiempo Real:** Recepción automatizada de reportes ópticos y ambientales enviados por microcontroladores ESP32-S3.
* **Trazabilidad de Incidentes:** Ciclo de vida completo de reportes (*Pendiente, En Revisión, Atendido*) con registro de causas, soluciones y bitácoras de cierre.
* **Seguridad por Capas:** Autenticación robusta de usuarios mediante Laravel Sanctum y protección de endpoints IoT mediante validación de tokens por hardware (`X-API-Token`).

---

## 🛠️ Stack Tecnológico

* **Backend:** Laravel 11 (API REST, Eloquent ORM, migraciones y seeders).
* **Frontend:** Vue 3 (Composition API), Vuetify 3, Vite, Tailwind CSS y Chart.js para analíticas dinámicas.
* **Base de Datos:** MySQL / MariaDB (`orisa_db`).
* **IoT / Hardware:** ESP32-S3 con conectividad Wi-Fi y protocolos HTTP POST.

---

## 📋 Guía Rápida de Instalación (Despliegue Local)

Asegúrate de tener instalado PHP 8.2+, Composer, Node.js y un servidor MySQL/MariaDB.

1. **Clonar el repositorio y configurar el entorno:**
   ```bash
   git clone <https://github.com/fabriziomarin2005-ops/orisa.git>
   cd orisa-platform
   cp .env.example .env
