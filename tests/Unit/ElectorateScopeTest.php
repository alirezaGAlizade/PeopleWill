<?php

use App\Services\ElectorateScope;

test('satisfactionFromUpvoteRatio is false when electorate is empty', function () {
    $scope = new ElectorateScope;

    $result = $scope->satisfactionFromUpvoteRatio(10, 0);

    expect($result['satisfied'])->toBeFalse()
        ->and($result['upvote_ratio'])->toBeNull();
});

test('satisfactionFromUpvoteRatio is true when more than half of electorate upvoted', function () {
    $scope = new ElectorateScope;

    $result = $scope->satisfactionFromUpvoteRatio(51, 100);

    expect($result['satisfied'])->toBeTrue()
        ->and($result['upvote_ratio'])->toBe(0.51);
});
