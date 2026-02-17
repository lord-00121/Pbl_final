"use strict";
var __awaiter = (this && this.__awaiter) || function (thisArg, _arguments, P, generator) {
    function adopt(value) { return value instanceof P ? value : new P(function (resolve) { resolve(value); }); }
    return new (P || (P = Promise))(function (resolve, reject) {
        function fulfilled(value) { try { step(generator.next(value)); } catch (e) { reject(e); } }
        function rejected(value) { try { step(generator["throw"](value)); } catch (e) { reject(e); } }
        function step(result) { result.done ? resolve(result.value) : adopt(result.value).then(fulfilled, rejected); }
        step((generator = generator.apply(thisArg, _arguments || [])).next());
    });
};
var __importDefault = (this && this.__importDefault) || function (mod) {
    return (mod && mod.__esModule) ? mod : { "default": mod };
};
Object.defineProperty(exports, "__esModule", { value: true });
exports.EmailService = void 0;
const nodemailer_1 = __importDefault(require("nodemailer"));
class EmailService {
    constructor() {
        this.transporter = nodemailer_1.default.createTransport({
            service: 'gmail',
            auth: {
                user: process.env.EMAIL_USER || 'your-email@gmail.com',
                pass: process.env.EMAIL_PASS || 'your-app-password'
            }
        });
    }
    sendEmail(to, subject, text, html) {
        return __awaiter(this, void 0, void 0, function* () {
            try {
                const mailOptions = {
                    from: process.env.EMAIL_USER || 'your-email@gmail.com',
                    to,
                    subject,
                    text,
                    html
                };
                yield this.transporter.sendMail(mailOptions);
                console.log('Email sent successfully');
            }
            catch (error) {
                console.error('Error sending email:', error);
                throw error;
            }
        });
    }
    sendReceiptEmail(userEmail, receiptData) {
        return __awaiter(this, void 0, void 0, function* () {
            const subject = 'Booking Receipt - Sports Venue Platform';
            const text = `Thank you for your booking! Receipt details: ${JSON.stringify(receiptData)}`;
            const html = `
            <h2>Booking Receipt</h2>
            <p>Thank you for your booking!</p>
            <p>Receipt ID: ${receiptData.id}</p>
            <p>Amount: $${receiptData.receiptDetails.amount}</p>
            <p>Date: ${receiptData.receiptDetails.date}</p>
        `;
            yield this.sendEmail(userEmail, subject, text, html);
        });
    }
}
exports.EmailService = EmailService;
