import mongoose, { Document, Schema } from 'mongoose';

export interface IVenue extends Document {
    name: string;
    location: string;
    amenities: string[];
    pricing: number;
    availability: boolean;
    images?: string[];
    description?: string;
    ownerId: string;
    createdAt: Date;
    updatedAt: Date;
}

const VenueSchema = new Schema<IVenue>({
    name: {
        type: String,
        required: true
    },
    location: {
        type: String,
        required: true
    },
    amenities: [{
        type: String
    }],
    pricing: {
        type: Number,
        required: true
    },
    availability: {
        type: Boolean,
        default: true
    },
    images: [{
        type: String
    }],
    description: {
        type: String
    },
    ownerId: {
        type: String,
        required: true
    }
}, {
    timestamps: true
});

export const Venue = mongoose.model<IVenue>('Venue', VenueSchema);