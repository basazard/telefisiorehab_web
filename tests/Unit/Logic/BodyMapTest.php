<?php

use App\Support\Onboarding\BodyMapGeometry;
use App\Support\Onboarding\BodyRegions;

function regionIds(array $view): array
{
    return array_column($view['regions'], 'id');
}

function coordinates(string $path): array
{
    preg_match_all('/-?\d+(?:\.\d+)?/', $path, $m);

    return array_map('floatval', $m[0]);
}

it('has 35 unique region ids', function () {
    expect(BodyRegions::IDS)->toHaveCount(35)
        ->and(array_unique(BodyRegions::IDS))->toHaveCount(35);
});

it('has an Indonesian label for every region', function () {
    foreach (BodyRegions::IDS as $id) {
        expect(BodyRegions::label($id))->not->toBeEmpty()->and(BodyRegions::label($id))->not->toBe($id);
    }

    expect(BodyRegions::label('Left Knee'))->toBe('Lutut kiri')
        ->and(BodyRegions::label('Right Shoulder'))->toBe('Bahu kanan')
        ->and(BodyRegions::label('Lower Back'))->toBe('Punggung bawah')
        ->and(BodyRegions::label('Nonexistent'))->toBe('Nonexistent');
});

it('rolls every region up into one of the PRD areas', function () {
    foreach (BodyRegions::IDS as $id) {
        expect(array_key_exists(BodyRegions::area($id), BodyRegions::AREA_LABELS))->toBeTrue();
    }

    expect(BodyRegions::area('Left Forearm'))->toBe('elbow')
        ->and(BodyRegions::area('Right Heel'))->toBe('ankle')
        ->and(BodyRegions::area('Lower Back'))->toBe('lower_back')
        ->and(BodyRegions::area('Upper Back'))->toBe('upper_back')
        ->and(BodyRegions::area('Glutes'))->toBe('pelvis')
        ->and(BodyRegions::area('Chest'))->toBe('other')
        ->and(BodyRegions::area('Neck'))->toBe('head_neck')
        ->and(BodyRegions::areaLabel('Left Knee'))->toBe('Lutut');

    expect(BodyRegions::AREA_LABELS)->toHaveCount(10);
});

it('draws every region id on at least one view and nothing else', function () {
    $all = array_merge(regionIds(BodyMapGeometry::front()), regionIds(BodyMapGeometry::back()));
    $unique = array_values(array_unique($all));
    sort($unique);
    $expected = BodyRegions::IDS;
    sort($expected);

    expect($unique)->toBe($expected);
});

it('has no duplicate ids inside a view', function () {
    foreach ([BodyMapGeometry::front(), BodyMapGeometry::back()] as $view) {
        $ids = regionIds($view);
        expect(array_unique($ids))->toHaveCount(count($ids));
    }
});

it('has 22 tappable regions on the front and 25 on the back', function () {
    expect(regionIds(BodyMapGeometry::front()))->toHaveCount(22)
        ->and(regionIds(BodyMapGeometry::back()))->toHaveCount(25);
});

it('has a Right region for every Left region in the same view', function () {
    foreach ([BodyMapGeometry::front(), BodyMapGeometry::back()] as $view) {
        $ids = regionIds($view);

        foreach ($ids as $id) {
            if (str_starts_with($id, 'Left ')) {
                expect(in_array('Right '.substr($id, 5), $ids, true))->toBeTrue();
            }
        }
    }
});

it('puts each body part on the right view', function () {
    $front = regionIds(BodyMapGeometry::front());
    $back = regionIds(BodyMapGeometry::back());

    expect(in_array('Chest', $front, true))->toBeTrue()
        ->and(in_array('Chest', $back, true))->toBeFalse()
        ->and(in_array('Lower Back', $back, true))->toBeTrue()
        ->and(in_array('Lower Back', $front, true))->toBeFalse()
        ->and(in_array('Left Heel', $back, true))->toBeTrue()
        ->and(in_array('Left Forearm', $front, true))->toBeTrue()      // new on the front
        ->and(in_array('Left Forearm', $back, true))->toBeTrue();

    foreach (['Shoulder', 'Elbow', 'Wrist', 'Knee', 'Ankle'] as $part) {
        expect(in_array("Left $part", $back, true))->toBeTrue()          // new on the back
            ->and(in_array("Right $part", $back, true))->toBeTrue();
    }
});

it('has valid closed paths inside the 200 x 440 canvas', function () {
    $paths = [BodyMapGeometry::SILHOUETTE, BodyMapGeometry::HEAD];

    foreach ([BodyMapGeometry::front(), BodyMapGeometry::back()] as $view) {
        foreach ($view['regions'] as $region) {
            $paths[] = $region['d'];
        }
    }

    foreach ($paths as $d) {
        expect($d)->toStartWith('M');
        expect(str_ends_with($d, 'Z'))->toBeTrue();

        foreach (coordinates($d) as $n) {
            expect($n)->toBeGreaterThanOrEqual(-1.0)->and($n)->toBeLessThanOrEqual(441.0);
        }
    }
});

it('has mirrored left and right shapes', function () {
    $front = array_column(BodyMapGeometry::front()['regions'], 'd', 'id');

    $left = coordinates($front['Left Knee']);
    $right = coordinates($front['Right Knee']);

    // x of the right shape mirrors the left one around x = 100 (first coordinate pair)
    expect(round($left[0] + $right[0], 1))->toBe(200.0)->and($left[1])->toBe($right[1]);
});

it('draws a face only on the front view and hair on both', function () {
    expect(BodyMapGeometry::front()['face'])->not->toBeEmpty()
        ->and(BodyMapGeometry::back()['face'])->toBe([])
        ->and(BodyMapGeometry::front()['hair'])->not->toBeEmpty()
        ->and(BodyMapGeometry::back()['hair'])->not->toBeEmpty()
        ->and(BodyMapGeometry::EARS)->toHaveCount(2);
});

it('has anatomy details and volume highlights for both views', function () {
    foreach ([BodyMapGeometry::front(), BodyMapGeometry::back()] as $view) {
        expect($view['details'])->not->toBeEmpty()->and($view['highlights'])->not->toBeEmpty();

        foreach ($view['highlights'] as $h) {
            expect($h)->toHaveCount(4);
        }
    }
});
