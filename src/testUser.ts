import mongoose from 'mongoose';
import { User } from './models/user';

async function main() {
    await mongoose.connect('mongodb://localhost:27017/sports-venue');
    const user = new User({
        email: 'test@example.com',
        password: 'password123',
        userType: 'seller',
        profileDetails: {
            name: 'Test Seller',
            phone: '1234567890',
            address: '123 Main St'
        }
    });
    await user.save();
    console.log('User saved:', user);
    await mongoose.disconnect();
}

main().catch(console.error);