import { Router } from 'express';
import { ReceiptController } from '../controllers/receiptController';

const router = Router();
const receiptController = new ReceiptController();

// Route to generate a receipt
router.post('/generate/:bookingId', receiptController.generateReceipt);

// Route to retrieve a specific receipt by ID
router.get('/:id', receiptController.getReceiptById);

// Route to send receipt via email
router.post('/send', receiptController.sendReceipt);

export default router;