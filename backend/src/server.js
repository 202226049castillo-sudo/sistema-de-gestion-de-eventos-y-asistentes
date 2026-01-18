const express = require("express");

const app = express();
const PORT = 3000;

app.use(express.json());

const eventosRoutes = require("./routes/eventos");
app.use("/api/eventos", eventosRoutes);

app.listen(PORT, () => {
  console.log(`Servidor corriendo en http://localhost:${PORT}`);
});
