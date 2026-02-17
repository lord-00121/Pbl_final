export interface User {
    id: string;
    email: string;
    password: string;
    userType: 'customer' | 'seller';
    profileDetails: {
        name: string;
        phone: string;
        address: string;
    };
}

export interface Venue {
    id: string;
    name: string;
    location: string;
    amenities: string[];
    pricing: number;
    availability: boolean;
}

export interface Booking {
    id: string;
    userId: string;
    venueId: string;
    bookingTime: Date;
    duration: number; // in hours
}

export interface Receipt {
    id: string;
    bookingId: string;
    userId: string;
    receiptDetails: {
        amount: number;
        date: Date;
        items: string[];
    };
}