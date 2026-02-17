import { Router } from 'express';
import { AuthController } from '../controllers/authController';

const router = Router();
const authController = new AuthController();

router.post('/signup', authController.signup);
router.post('/login', authController.login);
router.post('/admin/login', authController.adminLogin);

export default router;