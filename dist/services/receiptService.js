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
Object.defineProperty(exports, "__esModule", { value: true });
exports.ReceiptService = void 0;
const receipt_1 = require("../models/receipt");
const booking_1 = require("../models/booking");
const user_1 = require("../models/user");
const emailService_1 = require("../utils/emailService");
class ReceiptService {
    constructor() {
        this.emailService = new emailService_1.EmailService();
    }
    generateReceipt(bookingId) {
        return __awaiter(this, void 0, void 0, function* () {
            const booking = yield booking_1.Booking.findById(bookingId).populate('userId');
            if (!booking) {
                return null;
            }
            const receipt = new receipt_1.Receipt({
                bookingId: booking._id,
                userId: booking.userId,
                receiptDetails: {
                    amount: booking.totalAmount,
                    date: new Date(),
                    paymentMethod: 'Credit Card',
                    status: 'paid'
                }
            });
            yield receipt.save();
            return receipt;
        });
    }
    getReceiptById(receiptId) {
        return __awaiter(this, void 0, void 0, function* () {
            return yield receipt_1.Receipt.findById(receiptId);
        });
    }
    sendReceiptEmail(receiptId) {
        return __awaiter(this, void 0, void 0, function* () {
            const receipt = yield receipt_1.Receipt.findById(receiptId);
            if (!receipt) {
                throw new Error('Receipt not found');
            }
            const user = yield user_1.User.findById(receipt.userId);
            if (!user) {
                throw new Error('User not found');
            }
            yield this.emailService.sendReceiptEmail(user.email, receipt);
        });
    }
    createEmailContent(receipt) {
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
exports.ReceiptService = ReceiptService;
