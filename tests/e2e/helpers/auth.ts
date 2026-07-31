import { type Page, type Expect } from '@playwright/test';

const USERS = {
    admin: { email: 'admin@pdvsa.com', password: '123456' },
    tecnologia: { email: 'tecnologia@pdvsa.com', password: '123456' },
    programador: { email: 'programador@pdvsa.com', password: '123456' },
    facilitador: { email: 'facilitador@pdvsa.com', password: '123456' },
    participante: { email: 'participante@pdvsa.com', password: '123456' },
} as const;

export type UserRole = keyof typeof USERS;

export async function loginAs(page: Page, role: UserRole): Promise<void> {
    await page.goto('/login');
    await page.fill('#email', USERS[role].email);
    await page.fill('#password', USERS[role].password);
    await page.click('button[type="submit"]');
    await page.waitForURL('**/home');
}

export async function logout(page: Page): Promise<void> {
    await page.click('a[href*="logout"]');
    await page.waitForURL('**/login');
}
