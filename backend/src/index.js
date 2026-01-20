import express from 'express';
import eventosRoutes from './routes/eventos.routes.js';

const app = express();

app.use(express.json());

// Rutas
app.use('/api/eventos', eventosRoutes);

const PORT = 4000;
app.get('/', (req, res) => {
  res.send('Backend funcionando correctamente 🚀');
});

app.listen(PORT, () => {
  console.log(`Servidor funcionando en puerto ${PORT}`);
});
