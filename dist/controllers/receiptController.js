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
exports.ReceiptController = void 0;
const receiptService_1 = require("../services/receiptService");
class ReceiptController {
    constructor() {
        this.receiptService = new receiptService_1.ReceiptService();
    }
    generateReceipt(req, res) {
        return __awaiter(this, void 0, void 0, function* () {
            try {
                const bookingId = req.params.bookingId;
                const receipt = yield this.receiptService.generateReceipt(bookingId);
                res.status(200).json(receipt);
            }
            catch (error) {
                res.status(500).json({ message: 'Error generating receipt', error });
            }
        });
    }
    getReceiptById(req, res) {
        return __awaiter(this, void 0, void 0, function* () {
            try {
                const receiptId = req.params.id;
                const receipt = yield this.receiptService.getReceiptById(receiptId);
                res.status(200).json(receipt);
            }
            catch (error) {
                res.status(500).json({ message: 'Error retrieving receipt', error });
            }
        });
    }
    sendReceipt(req, res) {
        return __awaiter(this, void 0, void 0, function* () {
            try {
                const { receiptId } = req.body;
                yield this.receiptService.sendReceiptEmail(receiptId);
                res.status(200).json({ message: 'Receipt sent successfully' });
            }
            catch (error) {
                res.status(500).json({ message: 'Error sending receipt', error });
            }
        });
    }
}
exports.ReceiptController = ReceiptController;
