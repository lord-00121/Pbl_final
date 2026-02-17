import { Receipt, IReceipt } from '../models/receipt';
import { Booking } from '../models/booking';
import { User } from '../models/user';
import { EmailService } from '../utils/emailService';

export class ReceiptService {
    private emailService: EmailService;

    constructor() {
        this.emailService = new EmailService();
    }

    async generateReceipt(bookingId: string): Promise<IReceipt | null> {
        const booking = await Booking.findById(bookingId).populate('userId');
        if (!booking) {
            return null;
        }

        const receipt = new Receipt({
            bookingId: booking._id,
            userId: booking.userId,
            receiptDetails: {
                amount: booking.totalAmount,
                date: new Date(),
                paymentMethod: 'Credit Card', // This should come from payment processing
                status: 'paid'
            }
        });

        await receipt.save();
        return receipt;
    }

    async getReceiptById(receiptId: string): Promise<IReceipt | null> {
        return await Receipt.findById(receiptId);
    }

    async sendReceiptEmail(receiptId: string): Promise<void> {
        const receipt = await Receipt.findById(receiptId);
        if (!receipt) {
            throw new Error('Receipt not found');
        }

        const user = await User.findById(receipt.userId);
        if (!user) {
            throw new Error('User not found');
        }

        await this.emailService.sendReceiptEmail(user.email, receipt);
    }

    private createEmailContent(receipt: IReceipt): string {
        return `
            <h1>Your Booking Receipt</h1>
            <p>Thank you for your booking!</p>
            <p>Receipt ID: ${receipt._id}</p>
            <p>Amount: $${receipt.receiptDetails.amount}</p>
            <p>Date: ${receipt.receiptDetails.date}</p>
            <p>Payment Method: ${receipt.receiptDetails.paymentMethod}</p>
            <p>Status: ${receipt.receiptDetails.status}</p>
        `;
    }
}