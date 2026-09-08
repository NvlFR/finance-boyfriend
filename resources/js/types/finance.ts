import type { User } from './auth';

export type CoupleSpace = {
    id: number;
    name: string;
    invite_code: string;
    user_one_id: number;
    user_two_id: number | null;
    status: 'pending' | 'active';
    anniversary_date: string | null;
    dashboard_cover_path?: string | null;
    dashboard_cover_url?: string | null;
    user_one?: User;
    user_two?: User;
    partner?: User | null;
};

export type WalletType =
    'bank' | 'ewallet' | 'cash' | 'investment' | 'credit_card';

export type Wallet = {
    id: number;
    couple_space_id: number;
    user_id: number | null;
    name: string;
    type: 'personal' | 'joint';
    wallet_type: WalletType;
    account_number?: string | null;
    balance: number | string;
    currency: string;
    color: string;
    icon: string;
    is_active: boolean;
    user?: User;
};

export type Category = {
    id: number;
    couple_space_id: number | null;
    name: string;
    type: 'income' | 'expense' | 'both';
    icon: string;
    color: string;
    is_default: boolean;
};

export type TransactionType = 'income' | 'expense' | 'transfer';
export type TransactionScope = 'personal' | 'shared';
export type SplitType =
    'full_one' | 'full_two' | 'split_equal' | 'custom' | 'joint_fund';

export type TransactionSplit = {
    id: number;
    transaction_id: number;
    paid_by_user_id: number;
    user_one_amount: number | string;
    user_two_amount: number | string;
    split_type: SplitType;
    settled: boolean;
    paid_by_user?: User;
};

export type Transaction = {
    id: number;
    couple_space_id: number;
    user_id: number;
    wallet_id: number;
    to_wallet_id: number | null;
    category_id: number | null;
    type: TransactionType;
    scope: TransactionScope;
    amount: number | string;
    fee_amount: number | string;
    transaction_date: string;
    title: string | null;
    notes: string | null;
    receipt_image_path: string | null;
    client_reference?: string | null;
    source_type?: 'subscription' | 'wishlist' | 'budget' | null;
    source_id?: number | null;
    wallet?: Wallet;
    to_wallet?: Wallet;
    category?: Category;
    user?: User;
    split?: TransactionSplit;
};

export type SavingsMovement = {
    id: number;
    user_id: number;
    wallet_id: number | null;
    amount: number | string;
    notes: string | null;
    contributed_at: string;
    goal?: { id: number; name: string };
    wallet?: Wallet;
    user?: User;
};

export type InvestmentTransaction = {
    id: number;
    investment_id: number;
    user_id: number;
    wallet_id: number | null;
    type: 'buy' | 'sell';
    quantity: number | string;
    unit_price: number | string;
    gross_amount: number | string;
    fee_amount: number | string;
    realized_profit_loss: number | string;
    transaction_date: string;
    notes: string | null;
    user?: User;
    wallet?: Pick<Wallet, 'id' | 'name'>;
};

export type Investment = {
    id: number;
    couple_space_id: number;
    user_id: number | null;
    name: string;
    symbol: string | null;
    asset_type:
        'stock' | 'mutual_fund' | 'crypto' | 'gold' | 'deposit' | 'other';
    scope: 'personal' | 'shared';
    quantity: number | string;
    average_buy_price: number | string;
    current_price: number | string;
    realized_profit_loss: number | string;
    currency: string;
    is_active: boolean;
    user?: User | null;
    transactions?: InvestmentTransaction[];
};

export type Settlement = {
    id: number;
    couple_space_id: number;
    from_user_id: number;
    to_user_id: number;
    amount: number | string;
    payment_method: string;
    notes: string | null;
    settled_at: string;
    from_user?: User;
    to_user?: User;
};

export type SettlementDebt = {
    debtor_id: number | null;
    creditor_id: number | null;
    amount: number;
    debtor?: User;
    creditor?: User;
    message: string;
};

export type Trip = {
    id: number;
    couple_space_id: number;
    user_id: number;
    title: string;
    origin_name: string | null;
    destination_name: string | null;
    origin_lat: number | null;
    origin_lng: number | null;
    destination_lat: number | null;
    destination_lng: number | null;
    current_lat: number | null;
    current_lng: number | null;
    speed: number;
    max_speed: number;
    total_distance_km: number;
    status: 'active' | 'completed' | 'cancelled';
    notes: string | null;
    started_at: string;
    ended_at: string | null;
    updated_at?: string;
    user?: User;
};

export type TransactionDefaults = {
    amount?: number | string;
    title?: string;
    notes?: string;
    scope?: TransactionScope;
    type?: TransactionType;
    wallet_id?: number;
    category_id?: number | null;
    split_type?: SplitType;
    source_type?: 'subscription' | 'wishlist' | 'budget';
    source_id?: number;
};

export type BirthdaySurprisePayload = {
    id: number;
    startsAt: string;
    endsAt: string;
    recipientName: string;
    recipientAvatarUrl: string | null;
    senderName: string;
    senderAvatarUrl: string | null;
    openingMessage: string;
    appreciationMessage: string;
    loveLetter: string;
    closingMessage: string;
    photos: string[];
    vouchers: string[];
};
