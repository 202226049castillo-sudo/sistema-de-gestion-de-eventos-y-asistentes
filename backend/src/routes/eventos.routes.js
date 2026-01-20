import { Router } from 'express';
import { body, param, validationResult } from 'express-validator';

const router = Router();

// Middleware para manejar errores de validación y no repetir código
const validarCampos = (req, res, next) => {
  const errores = validationResult(req);
  if (!errores.isEmpty()) {
    return res.status(400).json({ errors: errores.array() });
  }
  next();
};

// GET evento por id
// Seguridad: Validamos que el ID sea un formato esperado (ej. numérico o MongoID)
router.get('/:id', [
  param('id').isAlphanumeric().withMessage('El ID debe ser alfanumérico'),
  validarCampos
], (req, res) => {
  res.json({ message: `Evento ${req.params.id}` });
});

// POST crear evento
// Seguridad: Sanitizamos (trim, escape) y validamos tipos de datos
router.post('/', [
  body('titulo').trim().notEmpty().escape().withMessage('El título es obligatorio'),
  body('fecha').isISO8601().toDate().withMessage('Fecha inválida'),
  body('capacidad').isInt({ min: 1 }).withMessage('La capacidad debe ser un número positivo'),
  validarCampos
], (req, res) => {
  res.json({ message: 'Evento creado de forma segura' });
});

// PUT actualizar evento
router.put('/:id', [
  param('id').isAlphanumeric(),
  body('titulo').optional().trim().escape(),
  validarCampos
], (req, res) => {
  res.json({ message: 'Evento actualizado (datos validados)' });
});

// DELETE eliminar evento
router.delete('/:id', [
  param('id').isAlphanumeric(),
  validarCampos
], (req, res) => {
  res.json({ message: 'Evento eliminado' });
});

export default router;
