import { Router } from "express";
import eventosRoutes from "./eventos.routes.js";

const router = Router();

router.use("/eventos", eventosRoutes);

export default router;
