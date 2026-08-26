import { type Page, type Expect } from '@playwright/test';

const USERS = {
    admin: { email: process.env.E2E_ADMIN_EMAIL ?? 'admin@pdvsa.com', password: process.env.E2E_ADMIN_PASSWORD ?? '123456' },
    tecnologia: { email: process.env.E2E_TECNOLOGIA_EMAIL ?? 'tecnologia@pdvsa.com', password: process.env.E2E_TECNOLOGIA_PASSWORD ?? '123456' },
    programador: { email: process.env.E2E_PROGRAMADOR_EMAIL ?? 'programador@pdvsa.com', password: process.env.E2E_PROGRAMADOR_PASSWORD ?? '123456' },
    facilitador: { email: process.env.E2E_FACILITADOR_EMAIL ?? 'facilitador@pdvsa.com', password: process.env.E2E_FACILITADOR_PASSWORD ?? '123456' },
    participante: { email: process.env.E2E_PARTICIPANTE_EMAIL ?? 'participante@pdvsa.com', password: process.env.E2E_PARTICIPANTE_PASSWORD ?? '123456' },
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
