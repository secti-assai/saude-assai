export interface User {
    id: string;
    name: string;
    email: string;
    profile: 'admin' | 'pharmacist' | 'doctor' | 'nurse' | 'citizen' | 'receptionist';
}

export type PageProps<T extends Record<string, unknown> = Record<string, unknown>> = T & {
    auth: {
        user: User | null;
    };
    [key: string]: unknown;
};

export type ManchesterColor = 'red' | 'orange' | 'yellow' | 'green' | 'blue';
