import nodemailer from 'nodemailer';

export class EmailService {
    private transporter: nodemailer.Transporter;

    constructor() {
        this.transporter = nodemailer.createTransport({
            service: 'gmail',
            auth: {
                user: process.env.EMAIL_USER || 'your-email@gmail.com',
                pass: process.env.EMAIL_PASS || 'your-app-password'
            }
        });
    }

    async sendEmail(to: string, subject: string, text: string, html?: string): Promise<void> {
        try {
            const mailOptions = {
                from: process.env.EMAIL_USER || 'your-email@gmail.com',
                to,
                subject,
                text,
                html
            };

            await this.transporter.sendMail(mailOptions);
            console.log('Email sent successfully');
        } catch (error) {
            console.error('Error sending email:', error);
            throw error;
        }
    }

    async sendReceiptEmail(userEmail: string, receiptData: any): Promise<void> {
        const subject = 'Booking Receipt - Sports Venue Platform';
        const text = `Thank you for your booking! Receipt details: ${JSON.stringify(receiptData)}`;
        const html = `
            <h2>Booking Receipt</h2>
            <p>Thank you for your booking!</p>
            <p>Receipt ID: ${receiptData.id}</p>
            <p>Amount: $${receiptData.receiptDetails.amount}</p>
            <p>Date: ${receiptData.receiptDetails.date}</p>
        `;

        await this.sendEmail(userEmail, subject, text, html);
    }
}
