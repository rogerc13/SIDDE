import { test, expect } from '@playwright/test';
import { loginAs, type UserRole } from './helpers/auth';

test.describe('Authorization', () => {
    const roleRoutes: Array<{
        route: string;
        allowed: UserRole[];
        denied: UserRole[];
    }> = [
        {
            route: '/u/usuarios',
            allowed: ['admin'],
            denied: ['tecnologia', 'programador', 'facilitador', 'participante'],
        },
        {
            route: '/u/facilitadores',
            allowed: ['admin', 'tecnologia'],
            denied: ['programador', 'facilitador', 'participante'],
        },
        {
            route: '/u/participantes',
            allowed: ['admin', 'programador'],
            denied: ['tecnologia', 'facilitador', 'participante'],
        },
        {
            route: '/u/areas',
            allowed: ['admin', 'tecnologia'],
            denied: ['programador', 'facilitador', 'participante'],
        },
        {
            route: '/u/ubicaciones',
            allowed: ['admin', 'tecnologia'],
            denied: ['programador', 'facilitador', 'participante'],
        },
        {
            route: '/u/acciones_formacion',
            allowed: ['admin', 'tecnologia'],
            denied: ['programador', 'facilitador', 'participante'],
        },
        {
            route: '/u/af_programadas',
            allowed: ['admin', 'tecnologia', 'programador'],
            denied: ['facilitador', 'participante'],
        },
        {
            route: '/u/mis_acciones',
            allowed: ['facilitador', 'participante'],
            denied: ['admin', 'tecnologia', 'programador'],
        },
        {
            route: '/reports',
            allowed: ['admin', 'tecnologia', 'programador'],
            denied: ['facilitador', 'participante'],
        },
        {
            route: '/database',
            allowed: ['admin', 'tecnologia', 'programador', 'facilitador', 'participante'],
            denied: [],
        },
    ];

    for (const { route, allowed, denied } of roleRoutes) {
        test.describe(`${route}`, () => {
            for (const role of allowed) {
                test(`${role} CAN access`, async ({ page }) => {
                    await loginAs(page, role);
                    await page.goto(route);

                    await expect(page).not.toHaveURL(/login/);
                });
            }

            for (const role of denied) {
                test(`${role} is DENIED`, async ({ page }) => {
                    await loginAs(page, role);
                    await page.goto(route);

                    await expect(page).toHaveURL(/home/);
                });
            }
        });
    }
});
