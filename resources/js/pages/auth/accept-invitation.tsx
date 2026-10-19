import { Button, Input, Label, Spinner } from '@heroui/react';
import { Form, Head } from '@inertiajs/react';
import InputError from '@/components/input-error';
import TextLink from '@/components/text-link';
import { login } from '@/routes';
import { store } from '@/routes/invitation';

type Props = {
    token: string;
    email: string;
    isValid: boolean;
    passwordRules: string;
};

export default function AcceptInvitation({
    token,
    email,
    isValid,
    passwordRules,
}: Props) {
    if (!isValid) {
        return (
            <>
                <Head title="Convite expirado" />

                <div className="space-y-6 text-center text-sm text-muted-foreground">
                    <p>
                        Este link de convite é inválido ou já expirou. Peça ao
                        administrador da escola que reenvie o convite.
                    </p>
                    <p>
                        Já definiu sua senha?{' '}
                        <TextLink href={login()}>Entrar</TextLink>
                    </p>
                </div>
            </>
        );
    }

    return (
        <>
            <Head title="Definir senha" />

            <Form
                {...store.form()}
                transform={(data) => ({ ...data, token, email })}
                resetOnSuccess={['password', 'password_confirmation']}
            >
                {({ processing, errors }) => (
                    <div className="grid gap-6">
                        <div className="grid gap-2">
                            <Label htmlFor="email">E-mail</Label>
                            <Input
                                variant="secondary"
                                fullWidth
                                id="email"
                                type="email"
                                value={email}
                                readOnly
                            />
                            <InputError message={errors.email} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="password">Senha</Label>
                            <Input
                                variant="secondary"
                                fullWidth
                                id="password"
                                name="password"
                                type="password"
                                autoComplete="new-password"
                                autoFocus
                                passwordrules={passwordRules}
                                required
                            />
                            <InputError message={errors.password} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="password_confirmation">
                                Confirmar senha
                            </Label>
                            <Input
                                variant="secondary"
                                fullWidth
                                id="password_confirmation"
                                name="password_confirmation"
                                type="password"
                                autoComplete="new-password"
                                passwordrules={passwordRules}
                                required
                            />
                            <InputError
                                message={errors.password_confirmation}
                            />
                        </div>

                        <Button
                            type="submit"
                            fullWidth
                            isDisabled={processing}
                            data-test="accept-invitation-button"
                        >
                            {processing && <Spinner />}
                            Definir senha e entrar
                        </Button>
                    </div>
                )}
            </Form>
        </>
    );
}

AcceptInvitation.layout = {
    title: 'Defina sua senha',
    description: 'Crie uma senha para acessar sua conta',
};
