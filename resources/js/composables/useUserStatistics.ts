import { ref, onMounted } from 'vue'
import { route } from 'ziggy-js'

interface UserStatistics {
    total_patrons: number;
    undergraduate_students: number;
    graduate_students: number;
    all_faculty: number;
    all_staff: number;
    all_library_staff: number;
}

export function useUserStatistics() {
    const statistics = ref<UserStatistics>({
        total_patrons: 0,
        undergraduate_students: 0,
        graduate_students: 0,
        all_faculty: 0,
        all_staff: 0,
        all_library_staff: 0,
    })

    const loading = ref(false)
    const error = ref<string | null>(null)

    const fetchStatistics = async () => {
        loading.value = true
        error.value = null

        try {
            const response = await fetch(route('users.api.statistics'))

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`)
            }

            const data = await response.json()
            statistics.value = data
        } catch (err) {
            console.error('Failed to fetch user statistics:', err)
            error.value = err instanceof Error ? err.message : 'Failed to fetch statistics'
        } finally {
            loading.value = false
        }
    }

    // Auto-fetch on mount
    onMounted(() => {
        fetchStatistics()
    })

    return {
        statistics,
        loading,
        error,
        fetchStatistics,
        refetch: fetchStatistics
    }
}
