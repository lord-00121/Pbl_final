"use strict";
Object.defineProperty(exports, "__esModule", { value: true });
const express_1 = require("express");
const receiptController_1 = require("../controllers/receiptController");
const router = (0, express_1.Router)();
const receiptController = new receiptController_1.ReceiptController();
// Route to generate a receipt
router.post('/generate/:bookingId', receiptController.generateReceipt);
// Route to retrieve a specific receipt by ID
router.get('/:id', receiptController.getReceiptById);
// Route to send receipt via email
router.post('/send', receiptController.sendReceipt);
exports.default = router;
