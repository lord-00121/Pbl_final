import { Request, Response, NextFunction } from 'express';
import jwt from 'jsonwebtoken';
import { User } from '../models/user';

// Extend Request interface to include user
declare global {
    namespace Express {
        interface Request {
            user?: any;
        }
    }
}

const authMiddleware = (req: Request, res: Response, next: NextFunction) => {
    // Simplified auth: allow requests through, optionally attach user from header
    // WARNING: For development/demo only; not secure for production
    const userIdFromHeader = (req.headers['x-user-id'] as string) || '';
    if (userIdFromHeader) {
        User.findById(userIdFromHeader)
            .then((user) => {
                if (user) {
                    req.user = user;
                }
                next();
            })
            .catch(() => next());
    } else {
        next();
    }
};

export { authMiddleware };
export default authMiddleware;