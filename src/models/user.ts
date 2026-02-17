import mongoose, { Document, Schema } from 'mongoose';

export interface IUser extends Document {
    email: string;
    password: string;
    userType: 'customer' | 'seller';
    profileDetails: {
        name: string;
        phone: string;
        address: string;
    };
    createdAt: Date;
    updatedAt: Date;
}

const UserSchema = new Schema<IUser>({
    email: {
        type: String,
        required: true,
        unique: true
    },
    password: {
        type: String,
        required: true
    },
    userType: {
        type: String,
        enum: ['customer', 'seller'],
        required: true
    },
    profileDetails: {
        name: {
            type: String,
            required: true
        },
        phone: {
            type: String,
            required: true
        },
        address: {
            type: String,
            required: true
        }
    }
}, {
    timestamps: true
});

export const User = mongoose.model<IUser>('User', UserSchema);