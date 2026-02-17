import { Request, Response } from 'express';
import { ReceiptService } from '../services/receiptService';

export class ReceiptController {
    private receiptService: ReceiptService;

    constructor() {
        this.receiptService = new ReceiptService();
    }

    public async generateReceipt(req: Request, res: Response): Promise<void> {
        try {
            const bookingId = req.params.bookingId;
            const receipt = await this.receiptService.generateReceipt(bookingId);
            res.status(200).json(receipt);
        } catch (error) {
            res.status(500).json({ message: 'Error generating receipt', error });
        }
    }

    public async getReceiptById(req: Request, res: Response): Promise<void> {
        try {
            const receiptId = req.params.id;
            const receipt = await this.receiptService.getReceiptById(receiptId);
            res.status(200).json(receipt);
        } catch (error) {
            res.status(500).json({ message: 'Error retrieving receipt', error });
        }
    }

    public async sendReceipt(req: Request, res: Response): Promise<void> {
        try {
            const { receiptId } = req.body;
            await this.receiptService.sendReceiptEmail(receiptId);
            res.status(200).json({ message: 'Receipt sent successfully' });
        } catch (error) {
            res.status(500).json({ message: 'Error sending receipt', error });
        }
    }
}