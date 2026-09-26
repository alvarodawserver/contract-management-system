import { Link } from '@inertiajs/react';
import type { Paginated } from '@/types';

type Props = Pick<Paginated<unknown>, 'meta' | 'links'>;

/**
 * Previous/next pager shared by every paginated list page. Renders nothing on a single page.
 */
export function Pagination({ meta, links }: Props) {
    if (meta.last_page <= 1) {
        return null;
    }

    return (
        <div className="flex items-center justify-center gap-4">
            {links.prev && (
                <Link href={links.prev} preserveScroll>
                    Previous
                </Link>
            )}

            <span className="text-muted-foreground text-sm">
                Page {meta.current_page} of {meta.last_page}
            </span>

            {links.next && (
                <Link href={links.next} preserveScroll>
                    Next
                </Link>
            )}
        </div>
    );
}
