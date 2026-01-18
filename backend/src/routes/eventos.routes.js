import { Router } from "express";

const router = Router();

router.get("/", (req, res) => {
  res.json({ mensaje: "Ruta eventos funcionando" });
});

export default router;
