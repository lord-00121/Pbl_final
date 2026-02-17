import { Router } from 'express';
import { UserController } from '../controllers/userController';
import authMiddleware from '../middleware/authMiddleware';

const router = Router();
const userController = new UserController();

// Route for getting user profile
router.get('/profile', authMiddleware, userController.getProfile);

// Route for updating user profile
router.put('/profile', authMiddleware, userController.updateUserProfile);

// Route for verifying seller
router.post('/verify-seller', authMiddleware, userController.verifySeller);

export default router;