import type { User } from './auth';

export type CoupleSpace = {
    id: number;
    name: string;
    invite_code: string;
    user_one_id: number;
    user_two_id: number | null;
    status: 'pending' | 'active';
    anniversary_date: string | null;
    user_one?: User;
    user_two?: User;
    partner?: User | null;
};

export type WalletType = 'bank' | 'ewallet' | 'cash' | 'investment' | 'credit_card';

export type Wallet = {
    id: number;
    couple_space_id: number;
    user_id: number | null;
    name: string;
    type: 'personal' | 'joint';
    wallet_type: WalletType;
    account_number: string | null;
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
    type: 'income' | 'expense';
    icon: string;
    color: string;
    is_default: boolean;
};

export type TransactionType = 'income' | 'expense' | 'transfer';
export type TransactionScope = 'personal' | 'shared';
export type SplitType = 'full_one' | 'full_two' | 'split_equal' | 'custom' | 'joint_fund';

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
    transaction_date: string;
    title: string | null;
    notes: string | null;
    receipt_image_path: string | null;
    wallet?: Wallet;
    to_wallet?: Wallet;
    category?: Category;
    user?: User;
    split?: TransactionSplit;
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
