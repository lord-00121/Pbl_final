import { Request, Response } from 'express';
import { UserService } from '../services/userService';

export class AuthController {
    private userService: UserService;

    constructor() {
        this.userService = new UserService();
    }

    async signup(req: Request, res: Response) {
        try {
            const { email, password, userType, profileDetails } = req.body;
            const user = await this.userService.createUser(email, password, userType, profileDetails);
            res.status(201).json({ message: 'User created successfully', user });
        } catch (error) {
            res.status(500).json({ message: 'Error creating user', error });
        }
    }

    async login(req: Request, res: Response) {
        try {
            const { email, password } = req.body;
            const result = await this.userService.loginUser(email, password);
            res.status(200).json(result);
        } catch (error) {
            res.status(401).json({ message: 'Invalid credentials', error });
        }
    }

    async adminLogin(req: Request, res: Response) {
        try {
            const { email, password } = req.body;
            // Hardcoded admin credentials
            const ADMIN_EMAIL = process.env.ADMIN_EMAIL || 'admin@example.com';
            const ADMIN_PASSWORD = process.env.ADMIN_PASSWORD || 'admin123';

            if (email === ADMIN_EMAIL && password === ADMIN_PASSWORD) {
                return res.status(200).json({
                    token: null,
                    user: {
                        _id: 'admin-fixed-id',
                        email: ADMIN_EMAIL,
                        userType: 'admin',
                        profileDetails: { name: 'Administrator' },
                    },
                });
            }

            return res.status(401).json({ message: 'Invalid admin credentials' });
        } catch (error) {
            res.status(401).json({ message: 'Invalid admin credentials', error });
        }
    }
}