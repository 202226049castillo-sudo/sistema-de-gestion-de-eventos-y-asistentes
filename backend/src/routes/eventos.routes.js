import { Router } from 'express';

const router = Router();

// GET todos los eventos
router.get('/', (req, res) => {
  res.json({
    message: 'Ruta de eventos funcionando'
  });
});

// GET evento por id (para el navegador)
router.get('/:id', (req, res) => {
  res.json({
    message: `Evento ${req.params.id}`
  });
});

// POST crear evento
router.post('/', (req, res) => {
  res.json({ message: 'Evento creado (pendiente BD)' });
});

// PUT actualizar evento
router.put('/:id', (req, res) => {
  res.json({ message: 'Evento actualizado (pendiente BD)' });
});

// DELETE eliminar evento
router.delete('/:id', (req, res) => {
  res.json({ message: 'Evento eliminado (pendiente BD)' });
});

export default router;
