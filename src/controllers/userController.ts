import { Request, Response } from 'express';
import { UserService } from '../services/userService';

export class UserController {
    private userService: UserService;

    constructor() {
        this.userService = new UserService();
    }

    async getProfile(req: Request, res: Response) {
        try {
            const userId = req.user.id; // Assuming user ID is stored in req.user
            const userProfile = await this.userService.getUserById(userId);
            res.status(200).json(userProfile);
        } catch (error) {
            res.status(500).json({ message: 'Error retrieving user profile', error });
        }
    }

    async updateUserProfile(req: Request, res: Response) {
        try {
            const userId = req.user.id;
            const updatedData = req.body;
            const updatedProfile = await this.userService.updateUser(userId, updatedData);
            res.status(200).json(updatedProfile);
        } catch (error) {
            res.status(500).json({ message: 'Error updating user profile', error });
        }
    }

    async verifySeller(req: Request, res: Response) {
        try {
            const userId = req.user.id;
            const verificationStatus = await this.userService.verifySeller(userId);
            res.status(200).json({ message: 'Seller verification status updated', verificationStatus });
        } catch (error) {
            res.status(500).json({ message: 'Error verifying seller', error });
        }
    }
}