import express from 'express';
import helmet from 'helmet';
import cors from 'cors';
import routes from './routes/index.js';

const app = express();
const PORT = process.env.PORT || 3000;

// --- CAPAS DE CIBERSEGURIDAD GLOBALES ---

// 1. Helmet: Configura cabeceras HTTP seguras (HSTS, CSP, XSS Filter, etc.)
app.use(helmet());

// 2. CORS: Limita quién puede hacer peticiones a tu backend
app.use(cors({
    origin: 'http://localhost:5500', // Cambiar al dominio real en producción
    methods: ['GET', 'POST', 'PUT', 'DELETE'],
    allowedHeaders: ['Content-Type', 'Authorization']
}));

// 3. Limitador de carga: Previene ataques de denegación de servicio (DoS)
app.use(express.json({ limit: '10kb' })); // No acepta cuerpos mayores a 10KB
app.use(express.urlencoded({ extended: true, limit: '10kb' }));

// --- RUTAS ---
app.use('/api', routes);

// 4. Manejo centralizado de errores (Evita revelar stack traces al cliente)
app.use((err, req, res, next) => {
    console.error(err.stack);
    res.status(500).json({
        ok: false,
        error: 'Error interno del servidor (Incidente registrado)'
    });
});

app.listen(PORT, () => {
    console.log(`Servidor seguro corriendo en el puerto ${PORT}`);
});
