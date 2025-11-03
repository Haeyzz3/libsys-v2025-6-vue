export const getStatusStyle = (status: string) => {
    switch (status.toLowerCase()) {
        case 'available':
            return { text: 'text-green-700', bg: 'bg-green-100' };
        case 'damaged':
            return { text: 'text-yellow-700', bg: 'bg-yellow-100' };
        case 'missing':
            return { text: 'text-red-700', bg: 'bg-red-100' };
        case 'borrowed':
            return { text: 'text-blue-700', bg: 'bg-blue-100' };
        case 'discarded':
            return { text: 'text-gray-700', bg: 'bg-gray-100' };
        case 'transferred':
            return { text: 'text-purple-700', bg: 'bg-purple-100' };
        default:
            return { text: 'text-gray-700', bg: 'bg-gray-100' };
    }
};
