export type AppNotification = {
    id: string;
    data: {
        kind?: 'activity';
        title: string;
        subject?: string;
        classroom?: string;
        url: string;
    };
    read_at: string | null;
    created_at: string;
};

export type Notifications = {
    unread_count: number;
    latest: AppNotification[];
};
