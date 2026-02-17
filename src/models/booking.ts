import mongoose, { Document, Schema } from 'mongoose';

export interface IBooking extends Document {
    userId: string;
    venueId: string;
    bookingTime: Date;
    duration: number; // duration in hours
    status: 'pending' | 'confirmed' | 'cancelled' | 'completed';
    totalAmount: number;
    createdAt: Date;
    updatedAt: Date;
}

const BookingSchema = new Schema<IBooking>({
    userId: {
        type: String,
        required: true
    },
    venueId: {
        type: String,
        required: true
    },
    bookingTime: {
        type: Date,
        required: true
    },
    duration: {
        type: Number,
        required: true
    },
    status: {
        type: String,
        enum: ['pending', 'confirmed', 'cancelled', 'completed'],
        default: 'pending'
    },
    totalAmount: {
        type: Number,
        required: true
    }
}, {
    timestamps: true
});

export const Booking = mongoose.model<IBooking>('Booking', BookingSchema);