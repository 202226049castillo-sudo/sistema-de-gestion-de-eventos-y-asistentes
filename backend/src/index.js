import express from "express";
import eventosRoutes from "./routes/eventos.routes.js";

const app = express();

app.use(express.json());

// rutas
app.use("/api/eventos", eventosRoutes);

const PORT = 3000;
app.listen(PORT, () => {
  console.log("Servidor funcionando en puerto", PORT);
});
