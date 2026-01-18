import { Router } from 'express';

const router = Router();

router.get('/', (req, res) => {
  res.json({ mensaje: 'Rutas funcionando correctamente' });
});

export default router;
