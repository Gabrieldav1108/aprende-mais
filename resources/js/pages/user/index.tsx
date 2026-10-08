import { Button, useOverlayState } from '@heroui/react';
import { Head } from '@inertiajs/react';
import { Plus } from 'lucide-react';
import { useState } from 'react';
import UserModal from '@/components/user/modal';
import UserTable from '@/components/user/table';
import type { Person } from '@/components/user/table';
import { useCan } from '@/hooks/use-can';
import { dashboard, user as userIndex } from '@/routes';

type Props = {
    people: Person[];
    roles: Record<string, string>;
};

export default function UserIndex({ people, roles }: Props) {
    const canManageUsers = useCan('users.manage');
    const createState = useOverlayState();
    const editState = useOverlayState();
    const [editing, setEditing] = useState<Person | null>(null);

    const edit = (person: Person) => {
        setEditing(person);
        editState.open();
    };

    return (
        <>
            <Head title="Usuários" />
            <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
                {canManageUsers && (
                    <div className="flex justify-end">
                        <Button onPress={createState.open}>
                            <Plus className="size-4" />
                            Novo usuário
                        </Button>
                    </div>
                )}
                <UserTable
                    people={people}
                    roles={roles}
                    onEdit={canManageUsers ? edit : undefined}
                />
            </div>

            {canManageUsers && <UserModal state={createState} roles={roles} />}
            {canManageUsers && editing && (
                <UserModal
                    key={editing.id}
                    state={editState}
                    person={editing}
                    roles={roles}
                />
            )}
        </>
    );
}

UserIndex.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: dashboard() },
        { title: 'Usuários', href: userIndex() },
    ],
};
