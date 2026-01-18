const express = require("express");
const router = express.Router();

router.get("/", (req, res) => {
  res.json({ mensaje: "Lista de eventos funcionando" });
});

module.exports = router;
