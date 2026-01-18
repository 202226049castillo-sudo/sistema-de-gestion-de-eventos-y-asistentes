import express from "express";
import routes from "./routes/index.js";

const app = express();

app.use(express.json());

app.use("/api", routes);

const PORT = 4000;
app.listen(PORT, () => {
  console.log("Servidor funcionando en puerto", PORT);
});
