import { CalendarDays } from 'lucide-react';
import { Input } from '@/components/ui/input';

export type SimpleDateRange = { from: string; to: string };

export function DateRangePicker({
    date,
    onUpdate,
}: {
    date: SimpleDateRange;
    onUpdate: (date: SimpleDateRange) => void;
}) {
    return (
        <div className="flex flex-wrap items-center gap-2 rounded-md border bg-background px-3 py-1.5">
            <CalendarDays className="h-4 w-4 text-muted-foreground" />
            <Input
                type="date"
                value={date.from}
                max={date.to}
                onChange={(event) =>
                    onUpdate({ ...date, from: event.target.value })
                }
                className="h-7 w-[135px] border-0 p-0 shadow-none focus-visible:ring-0"
                aria-label="Start date"
            />
            <span className="text-xs text-muted-foreground">to</span>
            <Input
                type="date"
                value={date.to}
                min={date.from}
                onChange={(event) =>
                    onUpdate({ ...date, to: event.target.value })
                }
                className="h-7 w-[135px] border-0 p-0 shadow-none focus-visible:ring-0"
                aria-label="End date"
            />
        </div>
    );
}
