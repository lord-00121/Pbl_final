import mongoose, { Document, Schema } from 'mongoose';

export interface IReceipt extends Document {
    bookingId: string;
    userId: string;
    receiptDetails: {
        amount: number;
        date: Date;
        paymentMethod: string;
        status: string;
    };
    createdAt: Date;
    updatedAt: Date;
}

const ReceiptSchema = new Schema<IReceipt>({
    bookingId: {
        type: String,
        required: true
    },
    userId: {
        type: String,
        required: true
    },
    receiptDetails: {
        amount: {
            type: Number,
            required: true
        },
        date: {
            type: Date,
            required: true
        },
        paymentMethod: {
            type: String,
            required: true
        },
        status: {
            type: String,
            required: true
        }
    }
}, {
    timestamps: true
});

export const Receipt = mongoose.model<IReceipt>('Receipt', ReceiptSchema);