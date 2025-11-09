<?php

namespace App\Services;

use App\Models\Item;
use App\Repositories\ItemRepository;
use App\Repositories\ItemCategoryRepository;
use InvalidArgumentException;

class ItemService
{
    private ItemRepository $itemRepository;
    private ItemCategoryRepository $itemCategoryRepository;

    public function __construct(ItemRepository $itemRepository, ItemCategoryRepository $itemCategoryRepository)
    {
        $this->itemRepository = $itemRepository;
        $this->itemCategoryRepository = $itemCategoryRepository;
    }

    /**
     * 특정 분류에 속한 모든 품목을 가져옵니다.
     * @param int $categoryId
     * @return array
     */
    public function getItemsByCategoryId(int $categoryId): array
    {
        return $this->itemRepository->findByCategoryId($categoryId);
    }

    /**
     * 새로운 품목을 생성합니다.
     * @param array $data
     * @return string
     */
    public function createItem(array $data): string
    {
        $item = Item::make($data);
        if (!$item->validate()) {
            throw new InvalidArgumentException(implode(', ', $item->getErrors()));
        }

        $category = $this->itemCategoryRepository->findById($data['category_id']);
        if (!$category) {
            throw new InvalidArgumentException('존재하지 않는 분류입니다.');
        }

        return $this->itemRepository->create($data);
    }

    // update, delete 등의 메소드는 추후 품목 관리 전용 페이지 개발 시 추가
}
