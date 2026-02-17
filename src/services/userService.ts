import { User, IUser } from '../models/user';
import bcrypt from 'bcryptjs';
import jwt from 'jsonwebtoken';

export class UserService {
    private userModel: typeof User;

    constructor() {
        this.userModel = User;
    }

    async createUser(email: string, password: string, userType: string, profileDetails: any) {
        const hashedPassword = await bcrypt.hash(password, 10);
        const newUser = new this.userModel({
            email,
            password: hashedPassword,
            userType,
            profileDetails,
        });
        return await newUser.save();
    }

    async loginUser(email: string, password: string, isAdmin: boolean = false) {
        const user = await this.userModel.findOne({ email });
        if (!user) {
            throw new Error('User not found');
        }
        
        if (isAdmin && user.userType !== 'seller') {
            throw new Error('Admin access denied');
        }
        
        const isMatch = await bcrypt.compare(password, user.password);
        if (!isMatch) {
            throw new Error('Invalid credentials');
        }
        // Return without JWT per simplified auth requirement
        return { token: null, user };
    }

    async getUserById(userId: string) {
        return await this.userModel.findById(userId);
    }

    async updateUser(userId: string, updateData: Partial<IUser>) {
        return await this.userModel.findByIdAndUpdate(userId, updateData, { new: true });
    }

    async deleteUser(userId: string) {
        return await this.userModel.findByIdAndDelete(userId);
    }

    async verifySeller(userId: string) {
        // This would typically involve additional verification logic
        return await this.userModel.findByIdAndUpdate(userId, { verified: true }, { new: true });
    }
}