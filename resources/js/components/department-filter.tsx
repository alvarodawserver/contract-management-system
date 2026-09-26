import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import type { Department } from '@/types';

const ALL = 'all';

type Props = {
    departments: Department[];
    value: number | null;
    onChange: (departmentId: number | null) => void;
};

/**
 * Shared department filter for every contract list. Hidden entirely when the current user
 * only has one department to begin with (a head, or an employee): there is nothing to choose.
 */
export function DepartmentFilter({ departments, value, onChange }: Props) {
    if (departments.length <= 1) {
        return null;
    }

    return (
        <div className="grid gap-2">
            <Label htmlFor="department_id">Department</Label>

            <Select
                value={value?.toString() ?? ALL}
                onValueChange={(next) =>
                    onChange(next === ALL ? null : Number(next))
                }
            >
                <SelectTrigger id="department_id" className="w-full sm:w-56">
                    <SelectValue />
                </SelectTrigger>

                <SelectContent>
                    <SelectItem value={ALL}>All departments</SelectItem>
                    {departments.map((department) => (
                        <SelectItem
                            key={department.id}
                            value={department.id.toString()}
                        >
                            {department.name}
                        </SelectItem>
                    ))}
                </SelectContent>
            </Select>
        </div>
    );
}
